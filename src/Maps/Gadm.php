<?php
namespace Pctco\Api\Maps;
use think\facade\Config;
use think\facade\Db;
use Naucon\File\File;
use Naucon\File\FileWriter;
class Gadm{
    /** 
     ** __construct
     *? @date 22/03/15 15:53
     *  @param $limit 每次更新多少条数据
     */
    function __construct ($config = []) {
        $this->config =
        array_merge([
            'limit'   =>   10
        ],$config);
        
        $this->config = (object)$this->config;

        $model = [
            'global'   =>  new \app\model\LibraryGlobal,
            'country'   =>  new \app\model\LibraryCountry,
            'DM'  => new \app\model\DatabaseManage
        ];
        
        $this->model = (object)$model;
    }
    /** 
     ** 从本地 Json 自动更新数据到数据库
     *? @date 22/03/15 14:43
     *! @return String
     */
    public function update(){
        $path = app()->getRootPath().'entrance'.DIRECTORY_SEPARATOR.'static'.DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.'json'.DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.'country';

        $file = new File($path.DIRECTORY_SEPARATOR.'update');
        // 没有路径不存在则自动创建
        if ($file->exists() === false) $file->mkdirs();
        $dir = 
        scandir($path.DIRECTORY_SEPARATOR.'update');

        $Jlist = [];
        foreach ($dir as $v) {
            if (strrchr($v,'.') === '.json') $Jlist[] = $v;  
        }

        if (empty($Jlist[0])) return date('h:i:s',time()).' Maps.Gadm: No Jons File: '.$path.DIRECTORY_SEPARATOR.'update';
        $itac = (int)$Jlist[0];
        if ($itac === 86) return date('h:i:s',time()).' Maps.Gadm: 86 has been banned from updating.';
        $this->model->DM->DBPartition('library_country',$itac);

        $json = new FileWriter($path.DIRECTORY_SEPARATOR.'update'.DIRECTORY_SEPARATOR.$Jlist[0], 'r', true);
        
        $arr = json_decode($json->read(),true);
        
        $data = array_slice($arr,0,$this->config->limit);
        
       
        if (empty($data)) {
            $json->delete();
            $datas = 
            $this->model->country
            ->partition('p'.$itac)
            ->where('status', 1)
            ->withoutField('status,time')
            ->select()
            ->toArray();

            $global = 
            $this->model->global->where('itac', $itac)
            ->field('abridge')
            ->find();
            
            
            file_put_contents($path.DIRECTORY_SEPARATOR.$global->abridge.DIRECTORY_SEPARATOR.'data.json',json_encode(['data'=>$datas]));

            return date('h:i:s',time()).' Maps.Gadm: Delete Json File: '.$Jlist[0];
        }
        for ($i=0; $i < $this->config->limit; $i++) {
            if (empty($arr[$i])) continue;
            $this->updata($arr[$i],$itac);
            unset($arr[$i]);
        }
        file_put_contents($json->getPathname(),json_encode(array_values($arr)));
        
        return date('h:i:s',time()).' Maps.Gadm '.$Jlist[0].': '.$this->config->limit.' pieces of data are left with '.number_format(count($arr)).' pieces of data.';
    }
    /** 
     ** 更新数据到数据库
     *? @date 22/03/15 15:59
     *  @param Array $data 一维数组数据
     *  @param Int $itac 国际电话区号 主要作用用于分区数据库查询
     *! @return 
     */
    public function updata($data,$itac){
        // 查询深度
        $nlname = ['NL_NAME_1','NL_NAME_2','NL_NAME_3','NL_NAME_4','NL_NAME_5','NL_NAME_6'];
        $name = ['NAME_1','NAME_2','NAME_3','NAME_4','NAME_5','NAME_6'];
        $partition = 'p'.$itac;
        foreach ($nlname as $kl => $vl) {
            if (!empty($data[$nlname[$kl]]) || !empty($data[$name[$kl]])) {

                if (empty($data[$nlname[$kl]])) $data[$nlname[$kl]] = $data[$name[$kl]];
               
                $updata = [
                    'itac' =>  $itac,
                    'level' =>  $kl + 1,
                    'nlname'    =>  $data[$nlname[$kl]],
                    'name'  =>  $data[$name[$kl]]
                ];

                if ($kl === 0) {
                    $updata['pid'] = 0;
                }else{
                    $nlnamev2 = explode('|',$data[$nlname[$kl - 1]]);
                    $nlnamevn = count($nlnamev2) === 2?$nlnamev2[1]:$nlnamev2[0];
                    // 查询父级id，作为pid
                    $updata['pid'] = 
                    $this->model->country
                    ->partition($partition)
                    ->where([
                        'itac' =>  $itac,
                        'level' =>  $kl,
                        'nlname'    =>  $nlnamevn,
                        'name'  =>  $data[$name[$kl - 1]]
                    ])
                    ->value('id');
                }

                $nlnamev = explode('|',$updata['nlname']);
                $updata['nlname'] = count($nlnamev) === 2?$nlnamev[1]:$nlnamev[0];

                $id = 
                $this->model->country
                ->partition($partition)
                ->where($updata)
                ->value('id');

                $updata['time'] = time();
                
                if ($id) {
                    $this->model->country
                    ->partition($partition)
                    ->where('id',$id)
                    ->update($updata);
                }else{
                    $this->model->country
                    ->partition($partition)
                    ->insert($updata);
                }
            }
        }
    }
}
