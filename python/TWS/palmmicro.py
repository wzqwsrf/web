import time
import json
import urllib.request
import urllib.parse

from _tgprivate import TG_TOKEN

class Palmmicro:
    def __init__(self):
        # URL to which you want to send the array
        self.strUrl = 'https://palmmicro.com/php/telegram.php?token=' + TG_TOKEN

        iChatId = self.GetTelegramChatId()
        self.arMsg = {
            'update_id': 886050244,
            'message': {
                'message_id': 6620,
                'from': {
                    'id': iChatId,
                    'is_bot': False,
                    'first_name': 'ny152',
                    'username': 'sz152',
                    'language_code': 'zh-hans'
                        },
                'chat': {
                    'id': iChatId,
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
    

    def GetTimerInterval(self):
        return 15 # to fetch data every 15 seconds 
    
    
    def GetTelegramChatId(self):
        return 992671436


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
        response.close()
        self.arData = response_data['text']
        return self.arData

    
    def GetPeerStr(self, strType):
        if strType == 'ask':
            return 'bid'
        elif strType == 'bid':
            return 'ask'
            
    
    def GetArbitrageResult(self, symbol, arPeerData, strType):
        arResult = {'ratio': 1.0}
        strPeerType = self.GetPeerStr(strType) 
        price = arPeerData[strPeerType + '_price']
        size = arPeerData[strPeerType + '_size'] 
        arReply = self.arData[symbol]
        strSizeIndex = strType + '_size'
        if strSizeIndex in arReply:
            if arReply[strSizeIndex] > 0 and price > 0:
                arResult['ratio'] = round(price / float(arReply[strType + '_price_hedge']), 4)
                iSize = min(size, arReply[strType + '_size_hedge'])
                arResult['size'] = iSize;
                arResult['size_hedge'] = int((iSize * arReply['hedge'] + 50) / 100) * 100
        return arResult

    
    def SendTelegramMsg(self, strMsg):
        url = 'https://api.telegram.org/bot' + TG_TOKEN + '/sendMessage?text=' + urllib.parse.quote_plus(strMsg) + '&chat_id=-1001346320717'

        # Send a GET request to the URL
        response = urllib.request.urlopen(url)
    
        # Read the response data
        data = response.read()
    
        # Decode the response data
        decoded_data = data.decode('utf-8')
    
        #print(decoded_data)  # Print the decoded response data
    
        response.close()  # Close the response object
