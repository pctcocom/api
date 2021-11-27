<?php
namespace Pctco\Api\Git\Gitee;
class Master{
   function __construct ($git) {
      $this->git = $git;
   }
   public function GetREADME(){
      $query = '/'.$this->git->username.'/'.$this->git->repositories.'/raw/master/README.md';

      try {
         $request = $this->git->QueryList::get($this->git->gitee->domain.$query)->getHtml();
         return $request;
      } catch (\Exception $e) {
         return false;
      }
   }
}
