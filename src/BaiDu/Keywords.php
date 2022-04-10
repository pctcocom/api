<?php
namespace Pctco\Api\BaiDu;
class Keywords{
    function __construct ($options = []) {
        $options = array_merge([
          'kw'   =>   false
        ],$options);

        $this->options = (object)$options;
    }
    /** 
     ** zhidao.baidu.com
     *? @date 22/02/09 12:46
     *  @param myParam1 Explain the meaning of the parameter...
     *  @param myParam2 Explain the meaning of the parameter...
     *! @return 
     */
    public function GetZhiDaoTag(){
        if ($this->options->kw === false) return false;
        $client = new \GuzzleHttp\Client();
        $proxy = $client->request('POST','https://zhidao.baidu.com/api/getTag',[
            'query'   =>  [
                'type'  =>  1,
                'wd'  =>  $this->options->kw
            ]
        ]);
        
        if ($proxy->getStatusCode() == 200) {
            $proxy->getHeaderLine('application/json; charset=utf8');
            $proxy = json_decode($proxy->getBody(),true);
        }

        if (empty($proxy)) return false;
        if ($proxy['errno'] === 0) return empty($proxy['data'])?false:$proxy['data'];

        return $proxy['errno'];
    }
}
