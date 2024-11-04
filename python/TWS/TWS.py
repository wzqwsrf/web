import time

from ibapi.client import EClient
from ibapi.wrapper import EWrapper
from ibapi.contract import Contract
from ibapi.order import Order

from palmmicro import Palmmicro

class IBKRWrapper(EWrapper):
    def __init__(self, client):
        self.client = client
        self.palmmicro = Palmmicro()
        self.data = {}
        self.timer_interval = 30  # Timer interval in seconds

        self.arOrderId = {'KWEB': 0, 'XOP': 0}
        self.arSize = {'KWEB': 200, 'XOP': 100}
        self.arBuyPrice = {'KWEB': 32.38, 'XOP': 114.65}
        self.arBuyStatus = {'KWEB': False, 'XOP': True}
        self.arSellPrice = {'KWEB': 32.4, 'XOP': 160.3}
        self.arSellStatus = {'KWEB': False, 'XOP': True}
        self.arBuyNext = {'KWEB': True, 'XOP': True}
        self.arContract = {}
        for symbol in ['KWEB', 'XOP']:
            contract = Contract()
            contract.symbol = symbol
            contract.secType = "STK"
            contract.exchange = "SMART"
            #contract.exchange = "OVERNIGHT"
            contract.currency = "USD"
            self.arContract[symbol] = contract


    def nextValidId(self, orderId: int):
        self.iOrderId = orderId
        self.start()


    def start(self):
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
            self.bid_price_trade(reqId)
        elif tickType == 2:  # Ask price
            data['ask_price'] = price
            self.ask_price_trade(reqId)
        #self.check_price_size(reqId)


    def tickSize(self, reqId, tickType, size):
        data = self.data[reqId]
        if tickType == 0:  # Bid size
            data['bid_size'] = size
        elif tickType == 3:  # Ask size
            data['ask_size'] = size
        #self.check_price_size(reqId)


    def orderStatus(self, orderId, status, filled, remaining, avgFillPrice, permId, parentId, lastFillPrice, clientId, whyHeld, mktCapPrice):
        print("Order Status - OrderId:", orderId, "Status:", status, "Filled:", filled, "Remaining:", remaining, "AvgFillPrice:", avgFillPrice)
        for symbol in ['KWEB', 'XOP']:
            if self.arOrderId[symbol] == orderId and remaining == 0:
                if self.arBuyStatus[symbol] == True:
                    self.arBuyStatus[symbol] = False
                    self.arBuyNext[symbol] = False
                elif self.arSellStatus[symbol] == True:
                    self.arSellStatus[symbol] = False
                    self.arBuyNext[symbol] = True

    
    def place_order(self, symbol, price, strAction):
        order = Order()
        order.action = strAction
        order.totalQuantity = self.arSize[symbol]
        order.orderType = "LMT"
        order.lmtPrice = price
        order.outsideRth = True

        # Place the order
        self.client.placeOrder(self.iOrderId, self.arContract[symbol], order)
        self.arOrderId[symbol] = self.iOrderId
        self.iOrderId += 1


    def ask_price_trade(self, reqId):
        data = self.data[reqId]
        symbol = data['symbol']
        if self.arBuyStatus[symbol] == False and self.arBuyNext[symbol] == True:
            buy_price = self.arBuyPrice[symbol]
            if data['ask_price'] > buy_price:
                self.place_order(symbol, buy_price, "BUY")
                self.arBuyStatus[symbol] = True


    def bid_price_trade(self, reqId):
        data = self.data[reqId]
        symbol = data['symbol']
        if self.arSellStatus[symbol] == False and self.arBuyNext[symbol] == False:
            sell_price = self.arSellPrice[symbol]
            if data['bid_price'] < sell_price:
                self.place_order(symbol, sell_price, "SELL")
                self.arSellStatus[symbol] = True


    def check_price_size(self, reqId):
        current_time = int(time.time())
        data = self.data[reqId]
        if all(data[attr] is not None for attr in ['bid_price', 'ask_price', 'bid_size', 'ask_size']):
             if current_time - data['last_processed_time'] >= self.timer_interval:
                self.process_price_size(reqId)
                data['last_processed_time'] = current_time


    def process_price_size(self, reqId):
        data = self.data[reqId]
        symbol = data['symbol']
        print(data)
        bid_price = data['bid_price']
        ask_price = data['ask_price']
        self.palmmicro.fetch_data('164906,162411')
        palmmicro_data = self.palmmicro.get_data()
        reply = palmmicro_data[symbol]
        #fSell = float(bid_price) / float(reply['peer_ask_price'])
        arResult = self.palmmicro.arbitrage_sell(symbol, bid_price, data['bid_size'])
        if arResult['ratio'] > 1.001:
            print("Sell", symbol, "at", bid_price, "with quantity less than", arResult['size'], "and buy", reply['symbol'], "at", reply['ask_price'], "---", round(arResult['ratio'], 3))
        fBuy = float(ask_price) / float(reply['peer_bid_price'])
        if fBuy < 0.999:
            print("Buy", symbol, "at", ask_price, "with quantity less than", data['ask_size'], "and sell", reply['symbol'], "at", reply['bid_price'], "---", round(fBuy, 3))
        print()  # Add a line break for better readability


class IBKRClient(EClient):
    def __init__(self, wrapper):
        EClient.__init__(self, wrapper)
        self.wrapper = wrapper


app = IBKRClient(IBKRWrapper(None))
app.wrapper = IBKRWrapper(app)
app.connect("127.0.0.1", 7497, clientId=0)

time.sleep(1)

app.run()
