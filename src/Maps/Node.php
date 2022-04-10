<?php
namespace Pctco\Api\Maps;
use Naucon\File\File;
class Node{
   /** 
    ** __construct
    *? @date 22/03/16 14:21
    *  @param String $key 申请的数据接口密钥
    *  @param Boolean $loop 是否开启 up 字段循环
    */
    function __construct ($config = []) {
        $this->config =
        array_merge([
            'loop'   => false,
            'order' =>  'asc'
        ],$config);
        
        $this->config = (object)$this->config;

        $model = [
            'global'   =>  new \app\model\LibraryGlobal,
            'country'   =>  new \app\model\LibraryCountry
        ];
      
      $this->model = (object)$model;
    }
    /** 
     ** 节点生成器
     *? @date 22/03/19 23:12
     *  根据 pid 数据生成节点 比如生成 json
     */
    public function builder(){
        $global = 
        $this->model->global->where('up_node', 1)
        ->field('itac,abridge,up_node')
        ->find();

        if (empty($global)) return date('h:i:s',time()).' Global: No "up_node" data to update';


        $find = 
        $this->model->country
        ->partition('p'.$global->itac)
        ->field('id,pid,nlname')
        ->where('up',0)
        ->order('id',$this->config->order)
        ->find();

        
        
        if (empty($find)) {
            if ($this->config->loop) {
                $this->model->country
                ->partition('p'.$global->itac)
                ->where('up', 1)
                ->update([
                   'up'  => 0
                ]);
            }
            $this->model->global
            ->where('itac', $global->itac)
            ->update([
                'up_node'   =>  0
            ]);
            return date('h:i:s',time()).' Maps.Node: No data to update';
        }


        $pathFile = app()->getRootPath().'entrance'.DIRECTORY_SEPARATOR.'static'.DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.'json'.DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.'country'.DIRECTORY_SEPARATOR.$global->abridge;

        $count = 
        $this->model->country
        ->partition('p'.$global->itac)
        ->where('up',1)
        ->count();

        // 刚刚开始必须生成一个 0.json
        if ($count === 0) {
            $data = 
            $this->model->country
            ->partition('p'.$global->itac)
            ->withoutField('time')
            ->where([
                'pid'   =>  0,
                'status'    =>  1
            ])
            ->select()
            ->toArray();

            file_put_contents($pathFile.DIRECTORY_SEPARATOR.'0.json',json_encode(['data'=>$data]));
        }


        $data = 
        $this->model->country
        ->partition('p'.$global->itac)
        ->withoutField('time')
        ->where([
            'pid'   =>    $find->id,
            'status'    =>  1
        ])
        ->select()
        ->toArray();

        if (!empty($data)) {
            file_put_contents($pathFile.DIRECTORY_SEPARATOR.$find->id.'.json',json_encode(['data'=>$data]));
        }

        $this->model->country
        ->partition('p'.$global->itac)
        ->where('id',$find->id)
        ->update([
            'up'  => 1,
            'sub'   =>  count($data)
        ]);

        return date('h:i:s',time()). ' Maps.Node: '.$global->itac.'('.$global->abridge.').id = '.$find->id.'('.$this->config->order.') '.$find->nlname.': Json saved '.number_format(count($data)).' , update complete '.number_format($count);
    }
}
