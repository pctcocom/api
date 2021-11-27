<?php
namespace Pctco\Api\Git\Gitee;
class User{
   function __construct ($git) {
      $this->git = $git;
   }
   /** 
    ** 获取用户信息 APIs (目前无法获取到企业用户)
    *? @date 21/11/27 19:17
   */
   public function info(){
      $query = '/users/'.$this->git->username;

      $request = $this->git->http->request('GET',$this->git->gitee->api.$query, ['http_errors' => false]);
      if ($request->getStatusCode() == 200) {
         $request->getHeaderLine('application/json; charset=utf8');
         $request = json_decode($request->getBody());
         return [
            'id'   =>   $request->id,
            'username'   =>   $this->git->username,
            'nickname'   =>   $request->name,
            'avatar'   =>   $request->avatar_url,
            'location'   =>   '',
            'company'   =>   $request->company,
            'signature'   =>   $request->bio,
            'group'   =>   $this->git->group,
            'blog'   =>   $request->blog,
            'email'   =>   $request->email,
            'twitter'   =>   '',
            'wechat'   => $request->wechat,
            'qq'   =>   $request->qq,
            'linkedin'   =>   $request->linkedin
         ];
      }
      return false;
   }
}
