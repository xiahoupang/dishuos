<?php
namespace Mobile\Controller;
use Think\Controller;

class CompanyController extends Controller{
    protected $jid,$sid,$cityid,$point='',$mid,$sj_style;
    public function _initialize(){
        $jid = I('get.jid',0,'intval')>0?I('get.jid',0,'intval'):cookie('jid');
        $enterPrise=D('Enterprise','Service');
        $Merwhere=array('jid'=>$jid);
        $jj = $enterPrise->MerGetField($Merwhere,'jid');
        $sj_style = $enterPrise->MerGetField($Merwhere,'sj_style');
        //获取jid
        if($jj){
			$this->jid = $jid;
			if($jid != cookie('jid')){
				cookie('jid',$jid,1000000);
				cookie('sid',null);
				cookie('ProductList',null);
			}
		}else{
			$this->display('Error:404');
			exit;
		}
        //判断当前是不是企业版
        if($sj_style==3){
            $this->sj_style = $sj_style;
        }else{
            $this->display('Error:404');
            exit;
        }
		$getIP=$this->getIP();
		$area = $this->getIpInfo($getIP); // 获取某个IP地址所在的位置
		if($area){
            if(cookie('userLat') && cookie('userLng')){
                $point['lat'] = cookie('userLat');
                $point['lng'] = cookie('userLng');
            }
		    $this->point=$point;
		    $this->assign('bdcityID',$area['address_detail']['city_code']);//显示当前baidu定位返回的城市id编号
		}
		
		//获取企业信息
		if($this->jid > 0){
		    $j = $enterPrise->MerFind(array('jid'=>$this->jid));
		    if(!$j){
		        $this->display('Error:404');
		        exit;
		    }else{
		        $statuswhere=array('mid'=>$j['mid']);
		        $mstatus=$enterPrise->MemGetField($statuswhere,'mstatus');
		        if($mstatus==0){
		            $this->display('Error:404');
		            exit;
		        }
		        $this->assign('Merchant',$j);
		    }
		}
		//微信分享接口设置
		$nwhere="jid=$this->jid and sid=-1 and show_notice=0";
		$notice = $enterPrise->NoticeGetField($nwhere,'addtime desc','notice',1);
		vendor('wxshare.jssdk');
		$jssdk = new \Vendor\wxshare\JSSDK(C('wx_AppID'), C('wx_AppSecret'));
		$signPackage = $jssdk->GetSignPackage();
		$this->assign('signPackage',$signPackage);
		$imgUrl = $j['merchant_logo'];
		$link = 'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"].'?share=1';
		if(strpos($link, 'jid') === false){
		    $link .= '&jid='.$this->jid;
		}
		$sharePackage = array(
		    'title' => $j['mabbreviation'],
		    'desc' =>   $notice,
		    'link' =>  $link,
		    'imgUrl' =>  'http://'.$_SERVER['HTTP_HOST'].$imgUrl,
		);
		$this->assign('sharePackage',$sharePackage);
		/* //如果是微信登录授权回调
		if(I('code') && I('state') == 'wxlogin'){
		    $code = I('code');
		    $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".C('wx_AppID')."&secret=".C('wx_AppSecret')."&code=".$code."&grant_type=authorization_code";
		    $res = json_decode($jssdk->httpGet($url));
		    $openid = $res->openid;
		    //查询用户是否存在 如果存在直接登录
		    if($openid){
		        $userid = M('fl_user')->where(array('flu_openid'=>$openid,'flu_sjid'=>$this->jid))->getField('flu_userid');
		        if($userid){
		            cookie('mid', $userid,604800);
		        }else{
		            $this->redirect('User/wxBackUrl',array('jid'=>$this->jid,'sid'=>$this->sid,'openid'=>$openid));
		        }
		    }else{
		        $this->display('Error:404');
		        exit;
		    }
		}
		//end */
		//获取用户id
		$this->mid = 0;
		$uid = cookie('mid')?cookie('mid'):0;
		if($uid){
		    $m = M('FlUser')->where(array('flu_userid'=>$uid,'flu_sjid'=>$this->jid,'flu_status'=>0))->find();
		    if($m){
		        $this->mid = $uid;
		        $this->userData = $m;
		    }else{
		        cookie('mid',null);
		    }
		}
		$this->assign('mid',$this->mid);
		$this->assign('jid',$jid);
		
    }
    
    //功能按钮
    public function funcMenu(){
        $enterPrise=D('Enterprise','Service');
        //公告消息
        $noticeWhere="jid=$this->jid and sid=-1 and show_notice=0";
        $notice = $enterPrise->NoticeSelect($noticeWhere,'addtime desc','id,notice');//获取公告消息的
        $numNotice=count($notice);
        $this->assign('notice',$notice);
        $this->assign('numNotice',$numNotice);
    }
    
    //获取客户端当前IP
    public function getIP($type = 0) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if($_SERVER['HTTP_X_REAL_IP']){//nginx 代理模式下，获取客户端真实IP
            $ip=$_SERVER['HTTP_X_REAL_IP'];
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {//客户端的ip
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {//浏览当前页面的用户计算机的网关
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];//浏览当前页面的用户计算机的ip地址
        }else{
            $ip=$_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }
    
    /**
     * 通过IP获取对应城市信息(该功能基于百度第三方IP库接口)
      * @param $ip IP地址,如果不填写，则为当前客户端IP
     * @return  如果成功，则返回数组信息，否则返回false
      */
     function getIpInfo($ip){
        if(empty($ip)) $ip=$this->get_client_ip();  //get_client_ip()为tp自带函数.
         $baiduUrl='http://api.map.baidu.com/location/ip?ak=0Fafcd0a5385f509b005594a4de38114&ip='.$ip.'&coor=bd09ll';
        $url='http://ip.taobao.com/service/getIpInfo.php?ip='.$ip;
        $result = file_get_contents($baiduUrl);
         $result = json_decode($result,true);
        if(!is_array($result['content'])) return false;
         return $result['content'];
    }
    
    /**
     * 执行CURL请求
     * @author: xialei<xialeistudio@gmail.com>
     * @param $url
     * @param array $params
     * @param bool $encode
     * @param int $method
     * @return mixed
     */
    private function async($url, $params = array(), $encode = true){
        $ch = curl_init();
            $url = $url . '?' . http_build_query($params);
            $url = $encode ? $url : urldecode($url);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_REFERER, '百度地图referer');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X; en-us) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $resp = curl_exec($ch);
        curl_close($ch);
        return $resp;
    }
    
    /**
     * ip定位
     * @param string $ip
     * @return array
     * @throws Exception
     */
    public function locationByIP($ip){
        $params = array(
            'ak' => '0Fafcd0a5385f509b005594a4de38114',
            'ip' => $ip,
            'coor' => 'bd09ll'//百度地图GPS坐标
        );
        $api = 'http://api.map.baidu.com/location/ip';
        $resp = $this->async($api, $params);
        $data = json_decode($resp, true);
        //返回地址信息
        return array(
            'address' => $data['content']['address'],
            'province' => $data['content']['address_detail']['province'],
            'city' => $data['content']['address_detail']['city'],
            'district' => $data['content']['address_detail']['district'],
            'street' => $data['content']['address_detail']['street'],
            'street_number' => $data['content']['address_detail']['street_number'],
            'city_code' => $data['content']['address_detail']['city_code'],
            'lng' => $data['content']['point']['x'],
            'lat' => $data['content']['point']['y']
        );
    }
    
    //企业版二维码
    public function makeQrcode(){
        $sid = I('sid');
        $jid = $this->jid;
        $size = 3;
        $qcUrl = U('Enterprise/index@yd',array('jid'=>$jid,'sid'=>$sid));
        vendor("phpqrcode.phpqrcode");
        $QRcode = new \QRcode();
        echo $QRcode::png($qcUrl, false, 'H', $size);
    }
    
    //企业版个人二维码
    public function makeUserQrcode(){
        $sid = I('sid');
        $jid = $this->jid;
        $size = 3;
        $suid = \Think\Crypt\Driver\Base64::encrypt($this->mid, C('CODEKEY'));
        $suid = base64_encode($suid);
        $qcUrl = U('Enterprise/index@yd',array('jid'=>$jid,'sid'=>$sid,'suid'=>$suid));
        vendor("phpqrcode.phpqrcode");
        $QRcode = new \QRcode();
        echo $QRcode::png($qcUrl, false, 'H', $size);
    }
    
    
    //去掉特殊字符
    public function replace_specialChar($str){
        $regex = "/\/|\~|\!|\@|\#|\\$|\%|\^|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";
        return preg_replace($regex,"",$str);
    }
    
    //新模板渲染
    public function newdisplay(){
        $jid = $this->jid == '0' ? I('jid','178') : $this->jid;
        $where=array('jid'=>$jid);
        $tpl_name = D('Enterprise','Service')->MerGetField($where,'theme');
        $this->assign('tpl_name', $tpl_name);
        $this->theme($tpl_name)->display();
    }
}