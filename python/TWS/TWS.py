import time
import json
import urllib.request
import urllib.parse

from ibapi.client import EClient
from ibapi.wrapper import EWrapper
from ibapi.contract import Contract

class Palmmicro:
    @staticmethod
    def get_data():
        strText = '@164906,162411'

        # URL to which you want to send the array
        strUrl = 'https://palmmicro.com/php/telegram.php'

        # Get the current time in seconds since the Unix epoch
        iTick = int(time.time())

        arMsg = {
            'update_id': 886050244,
            'message': {
                'message_id': 6620,
                'from': {
                    'id': 992671436,
                    'is_bot': False,
                    'first_name': 'ny152',
                    'username': 'sz152',
                    'language_code': 'zh-hans'
                        },
                'chat': {
                    'id': 992671436,
                    'first_name': 'ny152',
                    'username': 'sz152',
                    'type': 'private'
                        },
                'date': iTick,
                'text': strText
                       }
                }
        #print(arMsg)

        # Encode the array into a JSON formatted string
        strMsgJson = json.dumps(arMsg).encode('utf-8')
        #print(strMsgJson)

        # Send the array as JSON in the HTTP POST request
        req = urllib.request.Request(strUrl, data=strMsgJson, headers={'Content-Type': 'application/json'})
        response = urllib.request.urlopen(req)

        # Read and print the response content
        response_content = response.read().decode('utf-8')
        print(response_content)

        # Parse the JSON response and display the chat_id field
        response_data = json.loads(response_content)
        reply = response_data['text']
        return reply

class IBKRWrapper(EWrapper):
    def __init__(self, client):
        self.client = client
        self.palmmicro = Palmmicro()
        self.data = {}
        self.timer_interval = 30  # Timer interval in seconds

    def nextValidId(self, orderId: int):
        self.start()

    def start(self):
        #self.client.reqMarketDataType(3)
        for symbol in ['KWEB', 'XOP']:
            contract = Contract()
            contract.symbol = symbol
            contract.secType = "STK"
            contract.exchange = "SMART"
            #contract.exchange = "OVERNIGHT"
            contract.currency = "USD"

            self.client.reqMktData(len(self.data) + 1, contract, "", False, False, [])
            # Requesting extended trading hours data
            #self.client.reqMktData(len(self.data) + 1, contract, "", False, True, [])
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
        elif tickType == 2:  # Ask price
            data['ask_price'] = price
        self.check_price_size(reqId)

    def tickSize(self, reqId, tickType, size):
        data = self.data[reqId]
        if tickType == 0:  # Bid size
            data['bid_size'] = size
        elif tickType == 3:  # Ask size
            data['ask_size'] = size
        self.check_price_size(reqId)

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
        palmmicro_data = self.palmmicro.get_data()
        reply = palmmicro_data[symbol]
        fSell = float(bid_price) / float(reply['peer_ask_price'])
        if fSell > 1.005:
            print("Sell", symbol, "at", bid_price, "with quantity less than", data['bid_size'], "and buy", reply['symbol'], "at", reply['ask_price'], "---", round(fSell, 3))
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
