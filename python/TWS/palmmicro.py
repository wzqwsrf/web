import time
import json
import urllib.request
import urllib.parse

from _tgprivate import TG_TOKEN

class Palmmicro:
    def __init__(self):
        # URL to which you want to send the array
        self.strUrl = 'https://palmmicro.com/php/telegram.php'
        self.arMsg = {
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
                'date': 0,
                'text': ''
                       }
                    }
        self.arData = {}
        #print(f"Secret Key: {TG_TOKEN}")
    
    #@staticmethod
    def FetchData(self, strSymbols):
        arMessage = self.arMsg['message']
        # Get the current time in seconds since the Unix epoch
        arMessage['date'] = int(time.time())
        arMessage['text'] = f"@{strSymbols}"
        #print(self.arMsg)

        # Encode the array into a JSON formatted string
        strMsgJson = json.dumps(self.arMsg).encode('utf-8')
        #print(strMsgJson)

        # Send the array as JSON in the HTTP POST request
        req = urllib.request.Request(self.strUrl, data=strMsgJson, headers={'Content-Type': 'application/json'})
        response = urllib.request.urlopen(req)

        # Read and print the response content
        response_content = response.read().decode('utf-8')
        #print(response_content)

        # Parse the JSON response and display the chat_id field
        response_data = json.loads(response_content)
        self.arData = response_data['text']
        return self.arData

    def GetTimerInterval(self):
        return 30 # to fetch data every 30 seconds 
    
    def GetArbitrageResult(self, symbol, price, size, strType):
        arResult = {'ratio': 1.0}
        arReply = self.arData[symbol]
        if strType + '_size' in arReply:
            arResult['ratio'] = round(price / float(arReply[strType + '_price_hedge']), 4)
            iSize = min(size, arReply[strType + '_size_hedge'])
            arResult['size'] = iSize;
            arResult['size_hedge'] = int((iSize * arReply['hedge'] + 50) / 100) * 100
        return arResult
