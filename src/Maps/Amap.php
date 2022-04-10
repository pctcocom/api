<?php
namespace Pctco\Api\Maps;
class Amap{
   /** 
    ** __construct
    *? @date 22/03/16 14:21
    *  @param String $key 申请的数据接口密钥
    *  @param Boolean $loop 是否开启 up 字段循环
    */
   function __construct ($config = []) {
      $this->config =
      array_merge([
            'key'   =>   '',
            'loop'   => false
      ],$config);
      
      $this->config = (object)$this->config;

      $model = [
            'country'   =>  new \app\model\LibraryCountry,
            'DM'  => new \app\model\DatabaseManage,
            'GuzzleHttp'   =>  new \GuzzleHttp\Client
      ];
      
      $this->model = (object)$model;
  }
   /**
    * @name LinkageData
   * @describe 联动数据
   * @param  string
   * @return String
   **/
   public function update(){
      /** 
       ** 判断是否有数据
       *? @date 22/03/16 14:40
       */
      $this->model->DM->DBPartition('library_country',86);

      $find = 
      $this->model->country
      ->partition('p86')
      ->field('id,pid,nlname')
      ->where('up', 0)
      ->find();

      if (empty($find)) {
         $count = 
         $this->model->country
         ->partition('p86')
         ->count();
         if ($count === 0) {
            // 初始化刚刚开始...
            $pid = 0;
            $keywords = '中国';
         }else{
            if ($this->config->loop) {
               $this->model->country
               ->partition('p86')
               ->where('up', 1)
               ->update([
                  'up'  => 0
               ]);
            }
            
            return date('h:i:s',time()).' No data to update';
         }
      }else{
         $pid = $find->id;
         $keywords = $find->nlname;
      }


      $request = 
      $this->model->GuzzleHttp
      ->request('GET','https://restapi.amap.com/v3/config/district',[
         'query'   =>  [
             'subdistrict'  =>  1,
             'key'  =>  $this->config->key,
             's'  => 'rsv3',
             'output'   => 'json',
             'keywords' => $keywords
         ]
      ]);

      if ($request->getStatusCode() == 200) {
         $request->getHeaderLine('application/json; charset=utf8');
         $request = json_decode($request->getBody(),true);
      }

      $data = $request['districts'][0]['districts'];
      
      $level = [
         'province'  => 1,
         'city'   => 2,
         'district'  => 3,
         'street' => 4
      ];
      $inserts = 0;
      $updates = 0;
      foreach ($data as $v) {
         $update = [
            'itac'   => 86,
            'citycode'  => empty($v['citycode'])?'':$v['citycode'],
            'adcode' => $v['adcode'],
            'nlname' => $v['name'],
            'center' => $v['center'],
            'level'  => $level[$v['level']],
            'time'   => time()
         ];
         
         $finds = 
         $this->model->country
         ->partition('p86')
         ->where(['center'=>$v['center']])
         ->find();

         if (empty($finds)) {
            $update['pid'] = $pid;
            $this->model->country
            ->partition('p86')
            ->insert($update);
            $inserts++;
         }else{
            $this->model->country
            ->partition('p86')
            ->where('id',$finds->id)
            ->update($update);
            $updates++;
         } 
      }

      if (empty($find->id)) return date('h:i:s',time()). ' Data initialization.';

      $this->model->country
      ->partition('p86')
      ->where('id',$find->id)
      ->update(['up'  => 1]);

      return date('h:i:s',time()). ' 86.id = '.$find->id.' The json data was successfully updated: the keyword "'.$keywords.'" has a total of '.number_format(count($data)).' #insert('.$inserts.') #update('.$updates.') pieces of data';
   }
}
