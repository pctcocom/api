<?php
namespace Pctco\Api;
use QL\QueryList;
use Pctco\Verification\Regexp;
class Git{
   /**
   * @name __construct
   * @describe config
   * @param mixed $username 用户名
   **/
   function __construct ($config = []) {
      $config = array_merge([
         'url'   =>   false
      ],$config);


      $this->github = (object)[
         'api' => 'https://api.github.com',
         'raw' => 'https://raw.githubusercontents.com'
      ];
      $this->gitee = (object)[
         'api' => 'https://gitee.com/api/v5',
         'domain' => 'https://gitee.com'
      ];


      $this->url = $config['url'];
      $this->domain = false;
      $this->username = false;
      $this->repositories = false;

      $regexp = new Regexp($this->url);
      if ($regexp->check('html.href.link') !== false) {
         $url = explode('/',$this->url);
         $this->domain = $url[2];
         $this->username = $url[3];
         $this->repositories = empty($url[4])?'':$url[4];
      }

      switch ($this->domain) {
         case 'github.com':
         case 'hub.fastgit.org':
            $this->NewUser = new \Pctco\Api\Git\Github\User($this);
            $this->NewMaster = new \Pctco\Api\Git\Github\Master($this);
            $this->group = 1;
            break;
         case 'gitee.com':
            $this->NewUser = new \Pctco\Api\Git\Gitee\User($this);
            $this->NewMaster = new \Pctco\Api\Git\Gitee\Master($this);
            $this->group = 2;
            break;

         default:
            return false;
            break;
      }

      $this->http = new \GuzzleHttp\Client();
      $this->QueryList = new \QL\QueryList;
   }
   /**
   * @name getUserInfo
   * @describe 获取作者信息
   * @return Array
   **/
   public function getUserInfo(){
      if (empty($this->username)) return false;
      return $this->NewUser->info();
   }
   /**
   * @name Get README.md
   * @describe 获取 README.md text
   * @return Array
   **/
   public function GetREADME(){
      if (empty($this->username) && empty($this->repositories)) return '400: Invalid request (Git)';
      return $this->NewMaster->GetREADME();
   }
}
