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
    #print(iLen)
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
        self.strNextFuture = '202503'
        self.arDebug = {}


    def nextValidId(self, orderId: int):
        self.arHedge = ['SZ161127', 'SZ162411', 'SZ164906']
        self.arSymbol = ['KWEB', 'XBI', 'XOP']
        self.arOrder = {}
        self.arOrder['KWEB'] = GetOrderArray([28.64, 30.91, 31.47, 32.16, 33.18, 38.36], 200, 2, 4)
        self.arOrder['XBI'] = GetOrderArray([65.77, 110.82])
        self.arOrder['XOP'] = GetOrderArray([114.65, 160.3])
        self.arOrder['MES'] = GetOrderArray([3670.97, 6152.64])
        self.arOrder['SPX'] = GetOrderArray([3670.97, 6152.64])

        self.palmmicro = Palmmicro()
        self.client.StartStreaming(orderId)
        self.data = {}
        for strSymbol in self.arSymbol:
            iRequestId = self.client.StockReqMktData(strSymbol)
            self.data[iRequestId] = GetMktDataArray(strSymbol)
        #self.IndexStreaming()


    def error(self, reqId, errorCode, errorString, contract):
        print('Error:', reqId, errorCode, errorString)


    def tickPrice(self, reqId, tickType, price, attrib):
        data = self.data[reqId]
        if tickType == 1:  # Bid price
            data['bid_price'] = price
            self.BidPriceTrade(reqId)
            self.CheckPriceAndSize(reqId)
        elif tickType == 2:  # Ask price
            data['ask_price'] = price
            self.AskPriceTrade(reqId)
            self.CheckPriceAndSize(reqId)
        elif tickType == 4:
            data['last_price'] = price
            if IsMarketOpen():
                self.LastPriceTrade(reqId)


    def tickSize(self, reqId, tickType, size):
        data = self.data[reqId]
        if tickType == 0:  # Bid size
            data['bid_size'] = size
        elif tickType == 3:  # Ask size
            data['ask_size'] = size
        self.CheckPriceAndSize(reqId)


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
        strSymbol = 'MES'
        self.cal_mes = Calibration(strSymbol)
        iRequestId = self.client.FutureReqMktData(strSymbol)
        self.data[iRequestId] = GetMktDataArray(strSymbol)
        iRequestId = self.client.FutureReqMktData(strSymbol, self.strNextFuture)
        self.data[iRequestId] = GetMktDataArray(strSymbol, self.strNextFuture)

        strSymbol = 'SPX'
        self.cal_spx = Calibration(strSymbol)
        iRequestId = self.client.IndexReqMktData(strSymbol)
        self.data[iRequestId] = GetMktDataArray(strSymbol)


    def LastPriceTrade(self, reqId):
        data = self.data[reqId]
        strSymbol = data['symbol']
        if strSymbol == 'MES':
            if data['year_month'] == self.strNextFuture:
                self.cal_mes.Calc(data['last_price'])
            else:
                self.cal_mes.SetPrice(data['last_price'])
                self.cal_spx.Calc(data['last_price'])
        elif strSymbol == 'SPX':
            self.cal_spx.SetPrice(data['last_price'])

    
    def AskPriceTrade(self, reqId):
        data = self.data[reqId]
        strSymbol = data['symbol']
        arOrder = self.arOrder[strSymbol]
        if arOrder['BUY_id'] == -1:
            iPos = arOrder['BUY_pos']
            if iPos != -1:
                fPrice = arOrder['price'][iPos]
                if data['ask_price'] > fPrice:
                    arOrder['BUY_id'] = self.client.CallPlaceOrder(strSymbol, fPrice, arOrder['size'], 'BUY')


    def BidPriceTrade(self, reqId):
        data = self.data[reqId]
        strSymbol = data['symbol']
        arOrder = self.arOrder[strSymbol]
        if arOrder['SELL_id'] == -1:
            iPos = arOrder['SELL_pos']
            if iPos != -1:
                fPrice = arOrder['price'][iPos]
                if data['bid_price'] < fPrice:
                    arOrder['SELL_id'] = self.client.CallPlaceOrder(strSymbol, fPrice, arOrder['size'], 'SELL')


    def CheckPriceAndSize(self, reqId):
        if IsChinaMarketOpen():
            data = self.data[reqId]
            if all(data[attr] is not None for attr in ['bid_price', 'ask_price', 'bid_size', 'ask_size']):
                self.ProcessPriceAndSize(reqId)


    def GetSellBuyStr(self, strType):
        if strType == 'ask':
            return 'Sell'
        elif strType == 'bid':
            return 'Buy'

    
    def DebugPriceAndSize(self, strSymbol, strHedge, data, arReply, arResult, strType):
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
                if iSize >= 100 and ((fRatio > 1.01 and strType == 'ask') or (fRatio < 0.995 and strType == 'bid')):
                    self.palmmicro.SendTelegramMsg(strDebug)


    def ProcessPriceAndSize(self, reqId):
        data = self.data[reqId]
        strSymbol = data['symbol']
        arPalmmicro = self.palmmicro.FetchData(self.arHedge)
        for strHedge in self.arHedge:
            arReply = arPalmmicro[strHedge]
            if 'symbol_hedge' in arReply and arReply['symbol_hedge'] == strSymbol:
                for strType in ['ask', 'bid']:
                    arResult = self.palmmicro.GetArbitrageResult(strHedge, data, strType)
                    self.DebugPriceAndSize(strSymbol, strHedge, data, arReply, arResult, strType)


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


    def FutureReqMktData(self, strSymbol, strYearMonth = '202412'):
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
        if contract.exchange != 'OVERNIGHT':
            order.outsideRth = True

        if iOrderId == -1:
            iOrderId = self.iOrderId
            self.iOrderId += 1

        # Place the order
        self.placeOrder(iOrderId, contract, order)
        time.sleep(1)
        return iOrderId


app = MyEClient(MyEWrapper(None))
app.wrapper = MyEWrapper(app)
app.connect('127.0.0.1', 7497, clientId=0)

time.sleep(1)

app.run()
