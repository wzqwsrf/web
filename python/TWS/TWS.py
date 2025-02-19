import time

from ibapi.client import EClient
from ibapi.wrapper import EWrapper
from ibapi.contract import Contract
from ibapi.order import Order

from palmmicro import Palmmicro
from palmmicro import Calibration
from nyc_time import GetExchangeTime


def IsChinaMarketOpen():
    iTime = GetExchangeTime('SZSE')
    if iTime >= 915 and iTime <= 1130:
        return True
    elif iTime >= 1300 and iTime <= 1500:
        return True
    return False


def IsMarketOpen():
    iTime = GetExchangeTime()
    if iTime >= 930 and iTime <= 1600:
        return True
    return False


def GetOrderArray(arPrice, iSize = 100, iBuyPos = -1, iSellPos = -1):
    iLen = len(arPrice)
    if iSellPos >= iLen or iSellPos < -1:
        iSellPos = -1
    if iBuyPos  >= iLen or iBuyPos < -1:
        iBuyPos = -1
    ar = {'price': arPrice,
          'BUY_id': -1,
          'SELL_id': -1,
          'BUY_pos': iBuyPos,
          'SELL_pos': iSellPos,
          'size': iSize
         }
    return ar


def AdjustPriceArray(arPrice, fAdjust):
    arNew = []
    for fPrice in arPrice:
        arNew.append(round(round(4.0*fPrice*fAdjust)/4.0, 2))
    return arNew


def AdjustOrderArray(arOrder, fAdjust, iBuyPos = -1, iSellPos = -1):
    return GetOrderArray(AdjustPriceArray(arOrder['price'], fAdjust), arOrder['size'], iBuyPos, iSellPos)


def GetMktDataArray(strSymbol, strYearMonth = None):
    ar = {'symbol': strSymbol,
          'year_month': strYearMonth,
          'bid_price': None,
          'ask_price': None,
          'last_price': None,
          'bid_size': None,
          'ask_size': None
         }
    return ar


class MyEWrapper(EWrapper):
    def __init__(self, client):
        self.client = client
        #self.strNextFuture = '202506'
        self.arDebug = {}
        self.strMsg = ''
        

    def nextValidId(self, orderId: int):
        self.arHedge = ['SZ161127', 'SZ162411', 'SZ164906']
        self.arSymbol = ['KWEB', 'MES', 'XBI', 'XOP']
        self.arOrder = {}
        self.arOrder['KWEB'] = GetOrderArray([21.17, 26.43, 28.44, 32.43, 33.87, 35.15, 36.42], 200, 4, 5)
        self.arOrder['XBI'] = GetOrderArray([65.77, 110.82])
        self.arOrder['XOP'] = GetOrderArray([114.65, 160.3])
        self.arOrder['SPX'] = GetOrderArray([3857.84, 5183.12, 5703.46, 5707.43, 5956.64, 5977.78, 6070.78, 6088.88, 6125.86, 6163.78, 6205.85, 6508.39], 1)
        self.arOrder['MES'] = AdjustOrderArray(self.arOrder['SPX'], 1.003, 5, 9)
        self.palmmicro = Palmmicro()
        self.client.StartStreaming(orderId)
        self.data = {}
        for strSymbol in self.arSymbol:
            if strSymbol == 'MES':
                iRequestId = self.client.FutureReqMktData(strSymbol)
            else:
                iRequestId = self.client.StockReqMktData(strSymbol)
            self.data[iRequestId] = GetMktDataArray(strSymbol)
        self.IndexStreaming()


    def error(self, reqId, errorCode, errorString, contract):
        print('Error:', reqId, errorCode, errorString)


    def tickPrice(self, reqId, tickType, price, attrib):
        data = self.data[reqId]
        if tickType == 1:  # Bid price
            data['bid_price'] = price
            self.BidPriceTrade(data)
            self.CheckPriceAndSize(data)
        elif tickType == 2:  # Ask price
            data['ask_price'] = price
            self.AskPriceTrade(data)
            self.CheckPriceAndSize(data)
        elif tickType == 4:
            if IsMarketOpen():
                data['last_price'] = price
                self.LastPriceTrade(data)


    def tickSize(self, reqId, tickType, size):
        data = self.data[reqId]
        if tickType == 0:  # Bid size
            data['bid_size'] = size
        elif tickType == 3:  # Ask size
            data['ask_size'] = size
        self.CheckPriceAndSize(data)


    def orderStatus(self, orderId, status, filled, remaining, avgFillPrice, permId, parentId, lastFillPrice, clientId, whyHeld, mktCapPrice):
        print('Order Status - OrderId:', orderId, 'Status:', status, 'Filled:', filled, 'Remaining:', remaining, 'AvgFillPrice:', avgFillPrice)
        for strSymbol in self.arSymbol:
            arOrder = self.arOrder[strSymbol]
            arPrice = arOrder['price']
            iLen = len(arPrice)
            if arOrder['BUY_id'] == orderId:
                if status == 'Filled' and remaining == 0:
                    arOrder['BUY_id'] = -1
                    iOldSellPos = arOrder['SELL_pos']
                    self.IncSellPos(arOrder, 'BUY_pos', iLen)
                    arOrder['BUY_pos'] -= 1
                    if arOrder['SELL_id'] != -1 and arOrder['SELL_pos'] > -1 and arOrder['SELL_pos'] != iOldSellPos:
                        self.client.CallPlaceOrder(strSymbol, arPrice[arOrder['SELL_pos']], arOrder['size'], 'SELL', arOrder['SELL_id'])
                elif status != 'Submitted':
                    print('Unexpected BUY status ' + status)
            elif arOrder['SELL_id'] == orderId:
                if status == 'Filled' and remaining == 0:
                    arOrder['SELL_id'] = -1
                    iOldBuyPos = arOrder['BUY_pos']
                    arOrder['BUY_pos'] = arOrder['SELL_pos'] - 1
                    self.IncSellPos(arOrder, 'SELL_pos', iLen)
                    if arOrder['BUY_id'] != -1 and arOrder['BUY_pos'] > -1 and arOrder['BUY_pos'] != iOldBuyPos:
                        self.client.CallPlaceOrder(strSymbol, arPrice[arOrder['BUY_pos']], arOrder['size'], 'BUY', arOrder['BUY_id'])
                elif status != 'Submitted':
                    print('Unexpected SELL status ' + status)


    def IncSellPos(self, arOrder, strFrom, iLen):
        arOrder['SELL_pos'] = arOrder[strFrom] + 1
        if arOrder['SELL_pos'] >= iLen:
            arOrder['SELL_pos'] = -1


    def IndexStreaming(self):
        #strSymbol = 'MES'
        #iRequestId = self.client.FutureReqMktData(strSymbol, self.strNextFuture)
        #self.data[iRequestId] = GetMktDataArray(strSymbol, self.strNextFuture)
        strSymbol = 'SPX'
        self.spx_cal = Calibration(strSymbol)
        iRequestId = self.client.IndexReqMktData(strSymbol)
        self.data[iRequestId] = GetMktDataArray(strSymbol)


    def LastPriceTrade(self, data):
        strSymbol = data['symbol']
        if strSymbol == 'MES':
            #if data['year_month'] == self.strNextFuture:
            fAdjust = self.spx_cal.Calc(data['last_price'])
            if fAdjust > 1.0:
                arOrder = self.arOrder[strSymbol]
                arOld = arOrder['price']
                arNew = AdjustPriceArray(self.arOrder['SPX']['price'], fAdjust)
                for iIndex in range(len(arNew)):
                    fNew = arNew[iIndex]
                    if abs(fNew - arOld[iIndex]) > 0.01:
                        arOld[iIndex] = fNew
                        if arOrder['SELL_id'] != -1 and arOrder['SELL_pos'] == iIndex:
                            self.client.CallPlaceOrder(strSymbol, fNew, arOrder['size'], 'SELL', arOrder['SELL_id'])
                        if arOrder['BUY_id'] != -1 and arOrder['BUY_pos'] == iIndex:
                            self.client.CallPlaceOrder(strSymbol, fNew, arOrder['size'], 'BUY', arOrder['BUY_id'])
        elif strSymbol == 'SPX':
            self.spx_cal.SetPrice(data['last_price'])

    
    def AskPriceTrade(self, data):
        strSymbol = data['symbol']
        arOrder = self.arOrder[strSymbol]
        if arOrder['BUY_id'] == -1:
            iPos = arOrder['BUY_pos']
            if iPos != -1:
                fPrice = arOrder['price'][iPos]
                if data['ask_price'] > fPrice:
                    arOrder['BUY_id'] = self.client.CallPlaceOrder(strSymbol, fPrice, arOrder['size'], 'BUY')


    def BidPriceTrade(self, data):
        strSymbol = data['symbol']
        arOrder = self.arOrder[strSymbol]
        if arOrder['SELL_id'] == -1:
            iPos = arOrder['SELL_pos']
            if iPos != -1:
                fPrice = arOrder['price'][iPos]
                if data['bid_price'] < fPrice:
                    arOrder['SELL_id'] = self.client.CallPlaceOrder(strSymbol, fPrice, arOrder['size'], 'SELL')


    def CheckPriceAndSize(self, data):
        if IsChinaMarketOpen():
            if all(data[attr] is not None for attr in ['bid_price', 'ask_price', 'bid_size', 'ask_size']):
                self.ProcessPriceAndSize(data)


    def GetSellBuyStr(self, strType):
        if strType == 'ask':
            return 'Sell'
        elif strType == 'bid':
            return 'Buy'

    
    def DebugPriceAndSize(self, data, strSymbol, strHedge, arReply, arResult, strType):
        fRatio = arResult['ratio']
        iSize = arResult['size']
        if iSize > 0 and ((fRatio > 1.001 and strType == 'ask') or (fRatio < 0.999 and strType == 'bid')):
            strPeerType = self.palmmicro.GetPeerStr(strType)
            strDebug = str(round((fRatio - 1.0)*100.0, 2)) + '% '
            strDebug += self.GetSellBuyStr(strType) + ' ' + str(iSize) + ' ' + strSymbol + ' at ' + str(data[strPeerType + '_price']) + ' and '
            strDebug += self.GetSellBuyStr(strPeerType) + ' ' + str(arResult['size_hedge']) + ' ' + strHedge + ' at ' + arReply[strType + '_price']
            if strHedge not in self.arDebug or self.arDebug[strHedge] != strDebug:
                print(strDebug)
                self.arDebug[strHedge] = strDebug
                #if iSize >= 1 and ((fRatio > 1.001 and strType == 'ask') or (fRatio < 0.999 and strType == 'bid')):
                if iSize >= 100 and ((fRatio > 1.01 and strType == 'ask') or (fRatio < 0.995 and strType == 'bid')):
                    if self.palmmicro.IsFree() and self.strMsg != strDebug:
                        self.palmmicro.SendWechatMsg(strDebug)
                        self.palmmicro.SendTelegramMsg(strDebug)
                        self.strMsg = strDebug


    def ProcessPriceAndSize(self, data):
        strSymbol = data['symbol']
        arPalmmicro = self.palmmicro.FetchData(self.arHedge)
        for strHedge in self.arHedge:
            arReply = arPalmmicro[strHedge]
            if 'symbol_hedge' in arReply and arReply['symbol_hedge'] == strSymbol:
                for strType in ['ask', 'bid']:
                    arResult = self.palmmicro.GetArbitrageResult(strHedge, data, strType)
                    self.DebugPriceAndSize(data, strSymbol, strHedge, arReply, arResult, strType)


def GetContractExchange():
    iTime = GetExchangeTime()
    if iTime >= 350 and iTime <= 2000:
        return 'SMART'
    return 'OVERNIGHT'


class MyEClient(EClient):
    def __init__(self, wrapper):
        EClient.__init__(self, wrapper)
        self.wrapper = wrapper


    def StartStreaming(self, iOrderId):
        self.iOrderId = iOrderId
        self.iRequestId = 0
        self.arContract = {}


    def callReqMktData(self, strSymbol, contract):
        contract.symbol = strSymbol
        contract.currency = 'USD'
        self.arContract[strSymbol] = contract
        self.iRequestId += 1
        self.reqMktData(self.iRequestId, contract, '', False, False, [])
        return self.iRequestId


    def FutureReqMktData(self, strSymbol, strYearMonth = '202503'):
        contract = Contract()
        contract.secType = 'FUT'
        contract.exchange = 'CME'
        contract.lastTradeDateOrContractMonth = strYearMonth
        return self.callReqMktData(strSymbol, contract)


    def StockReqMktData(self, strSymbol):
        contract = Contract()
        contract.secType = 'STK'
        contract.exchange = GetContractExchange()
        return self.callReqMktData(strSymbol, contract)


    def IndexReqMktData(self, strSymbol):
        contract = Contract()
        contract.secType = 'IND'
        contract.exchange = 'CBOE'
        return self.callReqMktData(strSymbol, contract)


    def CallPlaceOrder(self, strSymbol, price, iSize, strAction, iOrderId = -1):
        contract = self.arContract[strSymbol]
        order = Order()
        order.action = strAction
        order.totalQuantity = iSize
        order.orderType = 'LMT'
        order.lmtPrice = price
        if strSymbol == 'MES':
            if IsMarketOpen() == False:
                return -1
        else:
            if contract.exchange != 'OVERNIGHT':
                order.outsideRth = True
        if iOrderId == -1:
            iOrderId = self.iOrderId
            self.iOrderId += 1
        self.placeOrder(iOrderId, contract, order)
        time.sleep(1)
        return iOrderId


app = MyEClient(MyEWrapper(None))
app.wrapper = MyEWrapper(app)
app.connect('127.0.0.1', 7497, clientId=0)
time.sleep(1)
app.run()
