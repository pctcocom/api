<?php
namespace Pctco\Api\Git\Github;
class Master{
   function __construct ($git) {
      $this->git = $git;
   }
   public function GetREADME(){
      $query = '/'.$this->git->username.'/'.$this->git->repositories.'/master/README.md';

      try {
         $request = $this->git->QueryList::get($this->git->github->raw.$query)->getHtml();
         return $request;
      } catch (\Exception $e) {
         return false;
      }
   }
}
