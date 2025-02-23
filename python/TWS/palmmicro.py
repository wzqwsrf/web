import time
import json
import requests

from _tgprivate import TG_TOKEN
from _tgprivate import WECHAT_KEY

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
        self.arWechatMsg = {
            'msgtype': 'text',
            'text': {
                'content': ''
                    }
                           }
        self.arData = {}
        self.iTimer = 0
        self.iTelegramTimer = 0
        self.strTelegramMsg = ''
        self.arTelegramMsg = []
   

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
        arMessage['date'] = int(time.time())
        strSymbols = ','.join(arSymbol)
        arMessage['text'] = f"@{strSymbols}"
        try:
            response = requests.post(self.strUrl, json=self.arMsg, headers={'Content-Type': 'application/json'})
            response.raise_for_status()  # Raise an exception for HTTP errors
            if response.status_code == 200:
                response_data = response.json()  # Parse the JSON response data
                #print('Response data:', response_data)
                self.arData.clear()
                self.arData = response_data['text']
            else:
                print('Failed to send POST request. Status code:', response.status_code)
        except requests.exceptions.RequestException as e:
            print('FetchData Error occurred:', e)
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

    
    def IsFree(self):
        iCur = int(time.time())
        if iCur - self.iTelegramTimer < self.GetTimerInterval()/2:
            return False
        self.iTelegramTimer = iCur
        return True


    def SendTelegramMsg(self, strMsg):
        url = 'https://api.telegram.org/bot' + TG_TOKEN + '/sendMessage?text=' + strMsg + '&chat_id=-1001346320717'
        try:
            response = requests.get(url)
            response.raise_for_status()  # Raise an exception for HTTP errors
            if response.status_code == 200:
                data = response.json()  # Assuming the response is in JSON format
                #print(data)
            else:
                print('Failed to retrieve data. Status code:', response.status_code)
        except requests.exceptions.RequestException as e:
            print('SendTelegramMsg Error occurred:', e)


    def SendWechatMsg(self, strMsg):
        url = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=' + WECHAT_KEY
        arText = self.arWechatMsg['text']
        arText['content'] = strMsg
        try:
            response = requests.post(url, json=self.arWechatMsg, headers={'Content-Type': 'application/json'})
            response.raise_for_status()  # Raise an exception for HTTP errors
            if response.status_code == 200:
                response_data = response.json()  # Parse the JSON response data
                #print('Response data:', response_data)
            else:
                print('Failed to send POST request. Status code:', response.status_code)
        except requests.exceptions.RequestException as e:
            print('SendWechatMsg Error occurred:', e)


    def SendMsg(self, strMsg):
        if self.strTelegramMsg != strMsg:
            self.strTelegramMsg = strMsg
            self.arTelegramMsg.append(strMsg)
            if self.IsFree():
                unique = set(self.arTelegramMsg)
                str = '\n\n'.join(unique)
                self.SendWechatMsg(str)
                self.SendTelegramMsg(str)
                self.arTelegramMsg.clear()



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

