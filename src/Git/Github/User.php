<?php
namespace Pctco\Api\Git\Github;
class User{
   function __construct ($git) {
      $this->git = $git;
   }
   public function info(){
      $query = '/users/'.$this->git->username;

      $request = $this->git->http->request('GET',$this->git->github->api.$query, ['http_errors' => false]);
      if ($request->getStatusCode() == 200) {
         $request->getHeaderLine('application/json; charset=utf8');
         $request = json_decode($request->getBody());
         return [
            'id'   =>   $request->id,
            'username'   =>   $this->git->username,
            'nickname'   =>   $request->name,
            'avatar'   =>   $request->avatar_url,
            'location'   =>   $request->location,
            'company'   =>   $request->company,
            'signature'   =>   $request->bio,
            'group'   =>   $this->git->group,
            'blog'   =>   $request->blog,
            'email'   =>   $request->email,
            'twitter'   => $request->twitter_username,
            'wechat'   => '',
            'qq'   =>   '',
            'linkedin'   =>   ''
         ];
      }
      return false;
   }
}
