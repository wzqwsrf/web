import time
import json
import urllib.request
import urllib.parse
import urllib.error

from _tgprivate import TG_TOKEN

class Palmmicro:
    def __init__(self):
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
        self.iTimer = 0
        self.iTelegramTimer = 0
    

    def GetTimerInterval(self):
        return 30 # to fetch data every 30 seconds, palmmicro.com only update once a minute. 
    
    
    def GetTelegramChatId(self):
        return 992671436


    #@staticmethod
    def FetchData(self, arSymbol):
        iCur = int(time.time())
        if iCur - self.iTimer < self.GetTimerInterval():
            return self.arData
    
        self.iTimer = iCur
        arMessage = self.arMsg['message']
        # Get the current time in seconds since the Unix epoch
        arMessage['date'] = int(time.time())
        strSymbols = ','.join(arSymbol)
        arMessage['text'] = f"@{strSymbols}"

        # Encode the array into a JSON formatted string
        strMsgJson = json.dumps(self.arMsg).encode('utf-8')

        # Send the array as JSON in the HTTP POST request
        req = urllib.request.Request(self.strUrl, data=strMsgJson, headers={'Content-Type': 'application/json'})
        try:
            response = urllib.request.urlopen(req)
        except urllib.error.URLError as e:
            print(f"FetchData error occurred: {e}")
            return self.arData

        # Read and print the response content
        response_content = response.read().decode('utf-8')

        # Parse the JSON response and display the chat_id field
        response_data = json.loads(response_content)
        response.close()

        self.arData.clear()
        self.arData = response_data['text']
        return self.arData

    
    def GetPeerStr(self, strType):
        if strType == 'ask':
            return 'bid'
        elif strType == 'bid':
            return 'ask'
            
    
    def GetArbitrageResult(self, symbol, arPeerData, strType):
        arResult = {'ratio': 1.0, 'size': 0}
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
        iCur = int(time.time())
        if iCur - self.iTelegramTimer < self.GetTimerInterval()/2:
            return
    
        self.iTelegramTimer = iCur
        url = 'https://api.telegram.org/bot' + TG_TOKEN + '/sendMessage?text=' + urllib.parse.quote_plus(strMsg) + '&chat_id=-1001346320717'
        try:
            response = urllib.request.urlopen(url)  # Send a GET request to the URL
        except urllib.error.URLError as e:
            print(f"SendTelegramMsg error occurred: {e}")
            # Handle the error gracefully, e.g., retrying, logging, etc.
            return

        data = response.read()                  # Read the response data
        decoded_data = data.decode('utf-8') # Decode the response data
        #print(decoded_data)  # Print the decoded response data
        response.close()  # Close the response object


class Calibration:
    def __init__(self, strSymbol):
        self.strSymbol = strSymbol
        self.fPrice = None
        self.Reset()

    def Reset(self):
        self.fTotal = 0.0
        self.iCount = 0

    def SetPrice(self, fPrice):
        self.fPrice = fPrice

    def Calc(self, fPeerPrice):
        if self.fPrice != None:
            fRatio = fPeerPrice/self.fPrice
            self.fTotal += fRatio
            self.iCount += 1
            if self.iCount > 100:
                fAvg = round(self.fTotal/self.iCount, 4)
                print(self.strSymbol, round(fRatio, 4), 'avg', fAvg)
                self.Reset()
                return fAvg
        return 0.0

