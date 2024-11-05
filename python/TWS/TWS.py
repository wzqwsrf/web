import time

from ibapi.client import EClient
from ibapi.wrapper import EWrapper
from ibapi.contract import Contract
from ibapi.order import Order

from palmmicro import Palmmicro

class MyEWrapper(EWrapper):
    def __init__(self, client):
        self.client = client
        self.palmmicro = Palmmicro()
        self.data = {}
        self.arOrder = {}

        arKWEB = self.GetDefaultOrderArray()
        arKWEB['size'] = 200
        arKWEB['buy_price'] = 32.33
        arKWEB['sell_price'] = 32.37
        self.arOrder['KWEB'] = arKWEB

        arXOP = self.GetDefaultOrderArray()
        arXOP['buy_status'] = True
        arXOP['buy_price'] = 114.65
        arXOP['sell_price'] = 160.3
        self.arOrder['XOP'] = arXOP

        self.arContract = {}
        for symbol in ['KWEB', 'XOP']:
            contract = Contract()
            contract.symbol = symbol
            contract.secType = "STK"
            contract.exchange = "SMART"
            #contract.exchange = "OVERNIGHT"
            contract.currency = "USD"
            self.arContract[symbol] = contract


    def GetDefaultOrderArray(self):
        ar = {
            'order_id': -1,
            'size': 100,
            'buy_status': False,
            'buy_price': 0.0,
            'sell_status': False,
            'sell_price': 0.0,
            'buy_next': True
                  }
        return ar


    def nextValidId(self, orderId: int):
        self.iOrderId = orderId
        
        # start market data streaming
        #self.client.reqMarketDataType(3)
        for symbol in ['KWEB', 'XOP']:
            self.client.reqMktData(len(self.data) + 1, self.arContract[symbol], "", False, False, [])
            self.data[len(self.data) + 1] = {
                'symbol': symbol,
                'last_processed_time': 0,
                'bid_price': None,
                'ask_price': None,
                'bid_size': None,
                'ask_size': None
            }


    def error(self, reqId, errorCode, errorString, contract):
        print("Error:", reqId, " ", errorCode, " ", errorString)


    def tickPrice(self, reqId, tickType, price, attrib):
        data = self.data[reqId]
        if tickType == 1:  # Bid price
            data['bid_price'] = price
            self.BidPriceTrade(reqId)
        elif tickType == 2:  # Ask price
            data['ask_price'] = price
            self.AskPriceTrade(reqId)


    def tickSize(self, reqId, tickType, size):
        data = self.data[reqId]
        if tickType == 0:  # Bid size
            data['bid_size'] = size
        elif tickType == 3:  # Ask size
            data['ask_size'] = size
        if self.IsOverNight(reqId):
            self.CheckPriceAndSize(reqId)


    def orderStatus(self, orderId, status, filled, remaining, avgFillPrice, permId, parentId, lastFillPrice, clientId, whyHeld, mktCapPrice):
        print("Order Status - OrderId:", orderId, "Status:", status, "Filled:", filled, "Remaining:", remaining, "AvgFillPrice:", avgFillPrice)
        for symbol in ['KWEB', 'XOP']:
            arOrder = self.arOrder[symbol]
            if arOrder['order_id'] == orderId and remaining == 0:
                if arOrder['buy_status'] == True:
                    arOrder['buy_status'] = False
                    arOrder['buy_next'] = False
                elif arOrder['sell_status'] == True:
                    arOrder['sell_status'] = False
                    arOrder['buy_next'] = True

    
    def PlaceOrder(self, symbol, price, strAction):
        arOrder = self.arOrder[symbol]
        contract = self.arContract[symbol]

        order = Order()
        order.action = strAction
        order.totalQuantity = arOrder['size']
        order.orderType = "LMT"
        order.lmtPrice = price
        if contract.exchange != "OVERNIGHT":
            order.outsideRth = True

        # Place the order
        self.client.placeOrder(self.iOrderId, contract, order)
        arOrder['order_id'] = self.iOrderId
        self.iOrderId += 1


    def AskPriceTrade(self, reqId):
        data = self.data[reqId]
        symbol = data['symbol']
        arOrder = self.arOrder[symbol]
        if arOrder['buy_status'] == False and arOrder['buy_next'] == True:
            buy_price = arOrder['buy_price']
            if data['ask_price'] > buy_price:
                self.PlaceOrder(symbol, buy_price, "BUY")
                arOrder['buy_status'] = True


    def BidPriceTrade(self, reqId):
        data = self.data[reqId]
        symbol = data['symbol']
        arOrder = self.arOrder[symbol]
        if arOrder['sell_status'] == False and arOrder['buy_next'] == False:
            sell_price = arOrder['sell_price']
            if data['bid_price'] < sell_price:
                self.PlaceOrder(symbol, sell_price, "SELL")
                arOrder['sell_status'] = True


    def CheckPriceAndSize(self, reqId):
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
        if contract.exchange == "OVERNIGHT":
            return True
        return False


    def ProcessPriceAndSize(self, reqId):
        data = self.data[reqId]
        symbol = data['symbol']
        bid_price = data['bid_price']
        ask_price = data['ask_price']
        arPalmmicro = self.palmmicro.FetchData('164906,162411')
        arReply = arPalmmicro[symbol]
        arResult = self.palmmicro.GetArbitrageResult(symbol, bid_price, data['bid_size'], "ask")
        fRatio = arResult['ratio']
        if fRatio > 1.005:
            print(data)
            print(arReply)
            print(fRatio, "Sell", arResult['size'], symbol, "at", bid_price, "and buy", arResult['size_hedge'], arReply['symbol'], "at", arReply['ask_price'])
        else:
            arResult = self.palmmicro.GetArbitrageResult(symbol, ask_price, data['ask_size'], "bid")
            fRatio = arResult['ratio']
            if fRatio < 0.999:
                print(data)
                print(arReply)
                print(fRatio, "Buy", arResult['size'], symbol, "at", ask_price, "and sell", arResult['size_hedge'], arReply['symbol'], "at", arReply['bid_price'])
        print()  # Add a line break for better readability


class MyEClient(EClient):
    def __init__(self, wrapper):
        EClient.__init__(self, wrapper)
        self.wrapper = wrapper


app = MyEClient(MyEWrapper(None))
app.wrapper = MyEWrapper(app)
app.connect("127.0.0.1", 7497, clientId=0)

time.sleep(1)

app.run()
