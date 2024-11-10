import time

from ibapi.client import EClient
from ibapi.wrapper import EWrapper
from ibapi.contract import Contract
from ibapi.order import Order

from palmmicro import Palmmicro
from nyc_time import GetExchangeTime

class MyEWrapper(EWrapper):
    def __init__(self, client):
        self.client = client


    def GetContractExchange(self):
        iTime = GetExchangeTime('NYSE')
        if iTime >= 350 and iTime <= 2000:
            return 'SMART'
        return 'OVERNIGHT'


    def GetDefaultOrderArray(self):
        ar = {
            'BUY_id': -1,
            'SELL_id': -1,
            'BUY_pos': -1,
            'SELL_pos': -1,
            'size': 100
                  }
        return ar


    def nextValidId(self, orderId: int):
        self.iOrderId = orderId
        
        self.palmmicro = Palmmicro()
        self.data = {}
        self.arOrder = {}

        arKWEB = self.GetDefaultOrderArray()
        arKWEB['price'] = [30.88, 32.55, 32.82]
        arKWEB['BUY_pos'] = 0
        arKWEB['SELL_pos'] = 2
        arKWEB['size'] = 200
        self.arOrder['KWEB'] = arKWEB

        arXOP = self.GetDefaultOrderArray()
        arXOP['price'] = [114.65, 160.3]
        self.arOrder['XOP'] = arXOP

        strExchange = self.GetContractExchange()
        self.arContract = {}
        # start market data streaming
        #self.client.reqMarketDataType(3)
        for strSymbol in ['KWEB', 'XOP']:
            contract = Contract()
            contract.symbol = strSymbol
            contract.secType = 'STK'
            contract.exchange = strExchange
            contract.currency = 'USD'
            self.arContract[strSymbol] = contract

            self.client.reqMktData(len(self.data) + 1, self.arContract[strSymbol], "", False, False, [])
            self.data[len(self.data) + 1] = {
                'symbol': strSymbol,
                'last_processed_time': 0,
                'bid_price': None,
                'ask_price': None,
                'bid_size': None,
                'ask_size': None
            }


    def error(self, reqId, errorCode, errorString, contract):
        print('Error:', reqId, errorCode, errorString)


    def tickPrice(self, reqId, tickType, price, attrib):
        data = self.data[reqId]
        if tickType == 1:  # Bid price
            data['bid_price'] = price
            self.BidPriceTrade(reqId)
        elif tickType == 2:  # Ask price
            data['ask_price'] = price
            self.AskPriceTrade(reqId)
        self.CheckPriceAndSize(reqId)


    def tickSize(self, reqId, tickType, size):
        data = self.data[reqId]
        if tickType == 0:  # Bid size
            data['bid_size'] = size
        elif tickType == 3:  # Ask size
            data['ask_size'] = size
        self.CheckPriceAndSize(reqId)


    def orderStatus(self, orderId, status, filled, remaining, avgFillPrice, permId, parentId, lastFillPrice, clientId, whyHeld, mktCapPrice):
        print('Order Status - OrderId:', orderId, 'Status:', status, 'Filled:', filled, 'Remaining:', remaining, 'AvgFillPrice:', avgFillPrice)
        if remaining == 0:
            for symbol in ['KWEB', 'XOP']:
                arOrder = self.arOrder[symbol]
                if arOrder['BUY_id'] == orderId:
                    arOrder['BUY_id'] = -1
                    if arOrder['SELL_id'] != -1:
                        self.client.cancelOrder(arOrder['SELL_id'])
                        arOrder['SELL_id'] = -1
                    arOrder['SELL_pos'] = arOrder['BUY_pos'] + 1
                    arOrder['BUY_pos'] -= 1
                elif arOrder['SELL_id'] == orderId:
                    arOrder['SELL_id'] = -1
                    if arOrder['BUY_id'] != -1:
                        self.client.cancelOrder(arOrder['BUY_id'])
                        arOrder['BUY_id'] = -1
                    arOrder['BUY_pos'] = arOrder['SELL_pos'] - 1
                    arOrder['SELL_pos'] += 1
                    if arOrder['SELL_pos'] == len(arOrder['price']):
                        arOrder['SELL_pos'] = -1

    
    def PlaceOrder(self, symbol, price, strAction):
        arOrder = self.arOrder[symbol]
        contract = self.arContract[symbol]

        order = Order()
        order.action = strAction
        order.totalQuantity = arOrder['size']
        order.orderType = 'LMT'
        order.lmtPrice = price
        if contract.exchange != 'OVERNIGHT':
            order.outsideRth = True

        # Place the order
        self.client.placeOrder(self.iOrderId, contract, order)
        arOrder[strAction + '_id'] = self.iOrderId
        self.iOrderId += 1


    def AskPriceTrade(self, reqId):
        data = self.data[reqId]
        symbol = data['symbol']
        arOrder = self.arOrder[symbol]
        if arOrder['BUY_id'] == -1:
            iPos = arOrder['BUY_pos']
            if iPos != -1:
                fPrice = arOrder['price'][iPos]
                if data['ask_price'] > fPrice:
                    self.PlaceOrder(symbol, fPrice, 'BUY')


    def BidPriceTrade(self, reqId):
        data = self.data[reqId]
        symbol = data['symbol']
        arOrder = self.arOrder[symbol]
        if arOrder['SELL_id'] == -1:
            iPos = arOrder['SELL_pos']
            if iPos != -1:
                fPrice = arOrder['price'][iPos]
                if data['bid_price'] < fPrice:
                    self.PlaceOrder(symbol, fPrice, 'SELL')


    def CheckPriceAndSize(self, reqId):
        if self.IsOverNight(reqId):
            current_time = int(time.time())
            data = self.data[reqId]
            if all(data[attr] is not None for attr in ['bid_price', 'ask_price', 'bid_size', 'ask_size']):
                if current_time - data['last_processed_time'] >= self.palmmicro.GetTimerInterval():
                    self.ProcessPriceAndSize(reqId)
                    data['last_processed_time'] = current_time


    def IsOverNight(self, reqId):
        data = self.data[reqId]
        symbol = data['symbol']
        contract = self.arContract[symbol]
        if contract.exchange == 'OVERNIGHT':
            return True
        return False


    def IsChinaMarketOpen(self):
        iTime = GetExchangeTime('SZSE')
        if iTime >= 915 and iTime <= 1130:
            return True
        elif iTime >= 1300 and iTime <= 1500:
            return True
        return False

    def ProcessPriceAndSize(self, reqId):
        data = self.data[reqId]
        symbol = data['symbol']
        bid_price = data['bid_price']
        ask_price = data['ask_price']
        arPalmmicro = self.palmmicro.FetchData('164906,162411')
        arReply = arPalmmicro[symbol]
        arResult = self.palmmicro.GetArbitrageResult(symbol, data, 'ask')
        fRatio = arResult['ratio']
        if fRatio > 1.005:
            print(data)
            print(arReply)
            strDebug = str(fRatio) + ' Sell ' + str(arResult['size']) + ' ' + symbol + ' at ' + str(bid_price) + ' and buy ' + str(arResult['size_hedge']) + ' ' + arReply['symbol'] + ' at ' + arReply['ask_price']
            print(strDebug)
            if fRatio > 1.01 and self.IsChinaMarketOpen():
                self.palmmicro.SendTelegramMsg(strDebug)
        arResult = self.palmmicro.GetArbitrageResult(symbol, data, 'bid')
        fRatio = arResult['ratio']
        if fRatio < 0.999:
            print(data)
            print(arReply)
            strDebug = str(fRatio) + ' Buy ' + str(arResult['size']) + ' ' + symbol + ' at ' + str(ask_price) + ' and sell ' + str(arResult['size_hedge']) + ' ' + arReply['symbol'] + ' at ' + arReply['bid_price']
            print(strDebug)
            if fRatio < 0.995 and self.IsChinaMarketOpen():
                self.palmmicro.SendTelegramMsg(strDebug)
        print('*')


class MyEClient(EClient):
    def __init__(self, wrapper):
        EClient.__init__(self, wrapper)
        self.wrapper = wrapper


app = MyEClient(MyEWrapper(None))
app.wrapper = MyEWrapper(app)
app.connect('127.0.0.1', 7497, clientId=0)

time.sleep(1)

app.run()
