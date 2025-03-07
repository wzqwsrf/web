import json
import math
import requests
import time

from _tgprivate import TG_TOKEN
from _tgprivate import WECHAT_KEY
from _tgprivate import WECHAT_REV_KEY

def _get_hedge_quantity(strType, arData):
    f_quantity = float(arData[strType + '_size']) / 100.0
    f_quantity = math.floor(f_quantity) * 100.0
    f_floor = math.floor(f_quantity / arData['hedge'])
    return int(f_floor)

def fund_reverse_adjust_position(f_position, f_val, f_old_val):
    return f_val / f_position - f_old_val * (1.0 / f_position - 1.0)

def qdii_get_peer_val(f_qdii, f_cny, f_calibration):
    return f_qdii * f_calibration / f_cny

def _ref_get_peer_val(strType, arData):
    f_qdii = fund_reverse_adjust_position(float(arData['position']), float(arData[strType + '_price']), float(arData['nav']))
    return str(qdii_get_peer_val(f_qdii, float(arData['CNY']), float(arData['calibration'])))


class Palmmicro:
    def __init__(self):
        self.arData = {}
        self.iTimer = 0
        self.arSendMsg = {'telegram':{'timer':0, 'count':13, 'msg':'', 'array_msg':[]},
                          'rev':{'timer':0, 'count':17, 'msg':'', 'array_msg':[]}
                         }

    def GetTelegramChatId(self):
        return 992671436

    def FetchSinaData(self, strSymbols):
        strUrl = f'http://hq.sinajs.cn/list={strSymbols.lower()}'
        try:
            response = requests.get(strUrl, headers={'Referer': 'https://finance.sina.com.cn'})
            if response.status_code == 200:
                arLine = response.text.split("\n")
                iLen = len('var hq_str_')
                for strLine in arLine:
                    if len(strLine) > iLen + len('="";'):
                        strSymbol = strLine[iLen:].split('"')[0]
                        strSymbol = strSymbol.rstrip('=')
                        strSymbol = strSymbol.upper()
                        if strSymbol in self.arData:
                            arData = self.arData[strSymbol]
                            arItem = strLine.split(',')
                            arData['bid_price'] = arItem[6]
                            arData['ask_price'] = arItem[7]
                            arData['bid_size'] = int(arItem[10])
                            arData['ask_size'] =  int(arItem[20])
                            arData['bid_size_hedge'] = _get_hedge_quantity('bid', arData);
                            arData['ask_size_hedge'] = _get_hedge_quantity('ask', arData);
                            arData['bid_price_hedge'] = _ref_get_peer_val('bid', arData)
                            arData['ask_price_hedge'] = _ref_get_peer_val('ask', arData)
            else:
                print('Failed to send request. Status code:', response.status_code)
        except requests.exceptions.RequestException as e:
            print('FetchSinaData Error occurred:', e)

    def FetchPalmmicroData(self, strSymbols):
        iChatId = self.GetTelegramChatId()
        arMsg = {
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
        arMessage = arMsg['message']
        arMessage['date'] = int(time.time())
        arMessage['text'] = f"@{strSymbols}"
        strUrl = 'https://palmmicro.com/php/telegram.php?token=' + TG_TOKEN
        try:
            response = requests.post(strUrl, json=arMsg, headers={'Content-Type': 'application/json'})
            response.raise_for_status()  # Raise an exception for HTTP errors
            if response.status_code == 200:
                response_data = response.json()  # Parse the JSON response data
                print('Response data:', response_data)
                #self.arData.clear()
                self.arData = response_data['text']
            else:
                print('Failed to send POST request. Status code:', response.status_code)
        except requests.exceptions.RequestException as e:
            print('FetchData Error occurred:', e)

    def FetchData(self, arSymbol):
        iCur = int(time.time())
        if iCur - self.iTimer < 19:
            return self.arData
        self.iTimer = iCur
        strSymbols = ','.join(arSymbol.keys())
        if not self.arData:
            self.FetchPalmmicroData(strSymbols)
        self.FetchSinaData(strSymbols)
        return self.arData
    
    #@staticmethod
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
    
    def IsFree(self, group):
        iCur = int(time.time())
        if iCur - self.arSendMsg[group]['timer'] < self.arSendMsg[group]['count']:
            return False
        self.arSendMsg[group]['timer'] = iCur
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

    def __send_wechat_msg(self, strMsg, strKey):
        url = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=' + strKey
        arWechatMsg = {
            'msgtype': 'text',
            'text': {
                'content': ''
                    }
                           }
        arText = arWechatMsg['text']
        arText['content'] = strMsg
        try:
            response = requests.post(url, json=arWechatMsg, headers={'Content-Type': 'application/json'})
            response.raise_for_status()  # Raise an exception for HTTP errors
            if response.status_code == 200:
                response_data = response.json()  # Parse the JSON response data
                #print('Response data:', response_data)
            else:
                print('Failed to send POST request. Status code:', response.status_code)
        except requests.exceptions.RequestException as e:
            print('SendWechatMsg Error occurred:', e)

    def SendWechatMsg(self, strMsg, group):
        if group == 'telegram':
            strKey = WECHAT_KEY
        elif group == 'rev':
            strKey = WECHAT_REV_KEY
        self.__send_wechat_msg(strMsg, strKey)

    def __send_msg(self, group):
        unique = set(self.arSendMsg[group]['array_msg'])
        str = '\n\n'.join(unique)
        self.SendWechatMsg(str, group)
        if group == 'telegram':
            self.SendTelegramMsg(str)
        self.arSendMsg[group]['array_msg'].clear()

    def SendMsg(self, strMsg, group='telegram'):
        if self.arSendMsg[group]['msg'] != strMsg:
            self.arSendMsg[group]['msg'] = strMsg
            self.arSendMsg[group]['array_msg'].append(strMsg)
            if self.IsFree(group):
                self.__send_msg(group)

    def SendOldMsg(self):
        for key, value in self.arSendMsg.items():
            #print('group', key, value)
            if self.IsFree(key):
                if len(value['array_msg']) > 0:
                    self.__send_msg(key)



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

