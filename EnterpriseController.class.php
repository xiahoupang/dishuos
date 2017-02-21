<?php
namespace Mobile\Controller;
/*
 * 企业版控制器
 */
class EnterpriseController extends CompanyController{
    //企业版首页
    public function index(){
        $jid=$this->jid;
        $indexD=D('Enterprise','Service');
        //首页模块控制及展示
        $indexShow=$indexD->getModulecontent($this->jid,'`order`');
        $this->assign('indexShow',$indexShow);
        //首页广告位
        $where=array('jid'=>$this->jid,'sid'=>0,'status'=>1);
        $type='ad_img,ad_type,ad_position';
        $order='addTime desc';
        $admiddle=$indexD->getAdmanage($where,$type,$order,'index',2);//中部广告
        $this->assign('admiddle',$admiddle);
        $adtop=$indexD->getAdmanage($where,$type,$order,'index',1);//顶部广告
        $this->assign('adtop',$adtop);
        $addown=$indexD->getAdmanage($where,$type,$order,'index',3);//底部广告
        $this->assign('addown',$addown);
        $adxuanfu=$indexD->getAdmanage($where,$type,$order,'index',0,4);//悬浮广告
        $adxuanfu['ad_position']=$adxuanfu['ad_position'][0];
        $this->assign('adxuanfu',$adxuanfu);
        //未读消息数
        $msgCount = M('user_msg')->where(array('sid'=>$this->sid,'userid'=>$this->mid,'status'=>0))->count();
        $this->assign('msgCount',$msgCount);
        //首页排序显示
        $shops = $this->getShopsCity('sid,theme');
        $sids = array_column($shops,'sid');
        $sids = implode(',',$sids);
        $indexOrder=$this->getOrderList($indexShow,$sids);
        foreach ($indexOrder as $k => $v){
            foreach ($shops as $k1 => $v1){
                if($v['sid']==$v1['sid'] && ($v1['theme']=='Hotel' || $v1['theme']=='ktv')){
                    $indexOrder[$k]['theme'] = 'index';
                }
            }
        }
        $this->assign('indexOrder',$indexOrder);
        //首页行业分类显示
        $where=array('jid'=>$jid,'status'=>1);
        $industry=$indexD->getIndustryCol($where,$type,$order);
        $num = count($industry);//统计推荐的行业个数
        foreach ($indexShow as $v){//前台最多显示8个栏目，去掉多余的显示；
            if($v['en_module_sign']=='advertisement' && $v['en_module_content']['col']){//查看是否有在栏目设置开店
                foreach ($industry as $k1=>$v1){//如果有开店设置的情况
                    unset($industry[$k1+6]);
                }
            }else{
                foreach ($industry as $k1=>$v1){//如果没有开店设置的情况
                    unset($industry[$k1+7]);
                }
            }
        }
        //客服
        $mertype = 'mnickname,serviceshow,mservetel,qq,weixin_name,weixin_pic,location_city';
        $merchant=$indexD->MerFind(array('jid'=>$jid),$mertype);
        $merchant['qq'] = "http://wpa.qq.com/msgrd?v=3&uin=".$merchant['qq']."&site=qq&menu=yes";
        //获取当前IP地址
        $getIP=$this->getIP();
        $area = $this->getIpInfo($getIP); // 获取某个IP地址所在的位置
        if($_GET['cityid']){
            $cityID=$indexD->getAddressFind(array('aid'=>(int)$_GET['cityid']),'aid,aname,bdcityID');
            if($cityID['aid'] != 100000){
                $cityID['aname']=mb_substr($cityID['aname'],0,-1,'utf-8');//去掉最后面一个市字
            }
            if($cityID['aid'] != cookie('cityid')){//如果ID不同于cookie中的ID，更新
                cookie('cityid',$cityID['aid'],24*60*60);
                cookie('bdcityID',$cityID['bdcityID'],24*60*60);
            }
        }else{
            if($merchant['location_city'] == 1 && $getIP != cookie('userIP')){
                if($area){
                    $cwhere['aname']=$area['address_detail']['city'];
                    $cityID=$indexD->getAddressFind($cwhere,'aid,aname,bdcityID');//查询该城市在数据库内的ID
                    if($getIP && cookie('userIP')!=$getIP){
                        $where['aid']=$cityID['aid'];
                        D('Address','Data')->setInc($where,'city_hot');//热门城市加1
                    }
                    $cityID['aname']=mb_substr($cityID['aname'],0,-1,'utf-8');//去掉最后面一个市字
                    if($cityID['aid'] != cookie('cityid')){//如果ID不同于cookie中的ID，更新
                        cookie('cityid',$cityID['aid'],24*60*60);
                        cookie('bdcityID',$cityID['bdcityID'],24*60*60);
                    }
                }else{
                    $cityID['aid']='330100';
                    $cityID['aname']='杭州';
                    $cityID['bdcityID']='179';
                    cookie('cityid',$cityID['aid'],24*60*60);
                }
            }else if($getIP != cookie('userIP')){
                $cityID['aid']='100000';
                $cityID['aname']='全国';
                cookie('cityid',$cityID['aid'],24*60*60);
            }
        }
        if($getIP && cookie('userIP')!=$getIP){
            $adquanpin=$indexD->getAdmanage($where,$type,$order,'index',0,3);//全屏广告
            $adquanpin['ad_position']=$adquanpin['ad_position'][0];
            $this->assign('adquanpin',$adquanpin);
            cookie('userIP',$getIP,24*60*60);
        }
        if(cookie('cityid')){
            $cityID=$indexD->getAddressFind(array('aid'=>(int)cookie('cityid')),'aid,aname,bdcityID');//查询该城市在数据库内的ID
            if($cityID['aid'] != 100000){
                $cityID['aname']=mb_substr($cityID['aname'],0,-1,'utf-8');//去掉最后面一个市字
            }
            $this->assign('city',$cityID);
        }else{
            $this->assign('city',$cityID);
        }
        $this->assign('userIP',cookie('userIP'));
        $this->assign('merchant',$merchant);
        $this->assign('num',$num);
        $this->assign('industry',$industry);
        $this->assign('page_current','index');
        $this->funcMenu();
        $this->newdisplay();
    }
    
    //品牌列表
    public function brand(){
        $jid=$this->jid;
        $where=array('sid'=>$jid);
        $brand=D('Enterprise','Service')->getBlandSelect($where,$limit='','brandtime asc');
        $this->assign('brand',$brand);
        $this->newdisplay();
    }
    
    //品牌商品列表
    public function brandGoods(){
        $type='gid,gname,sid,goprice,gdprice,gstock,gimg,(gsales+sale_num) AS zhongshu';
        $enter=D('Enterprise','Service');
        //广告投放
        $where=array('jid'=>$this->jid,'sid'=>0,'status'=>1);
        $adtype='ad_img,ad_type,ad_position';
        $order='addTime desc';
        $adid='brands';
        $adtop=$enter->getAdmanage($where,$adtype,$order,$adid,1);//顶部广告
        $this->assign('adtop',$adtop);
        $addown=$enter->getAdmanage($where,$adtype,$order,$adid,3);//底部广告
        $this->assign('addown',$addown);
        $adxuanfu=$enter->getAdmanage($where,$adtype,$order,$adid,0,4);//悬浮广告
        $adxuanfu['ad_position']=$adxuanfu['ad_position'][0];
        $this->assign('adxuanfu',$adxuanfu);
        //查询该企业版及城市下的店铺
        $shops=$this->getShopsCity('sid,theme');
        if($_POST['page']&&$_POST['bid']){
            $id=(int)$_POST['bid'];
            if($id<=0){
                $this->display('Error:404');
                exit;
            }
            $page=(int)$_POST['page']<2?2:(int)$_POST['page'];
            $where=array('bid'=>$id,'gstatus'=>1);
            $data = D('Enterprise','Service')->getGoodsSelect($where,$type,'create_time desc',$page);
            $goodsList=array();
            foreach ($data as $v){//遍历把商铺下的商品取出;
                foreach ($shops as $v1){
                    if($v['sid']==$v1['sid'] && ($v1['theme']=='Hotel' || $v1['theme']=='ktv')){
                        $v['theme'] = 'index';
                        $goodsList[]=$v;
                    }else if($v['sid']==$v1['sid']){
                        $goodsList[]=$v;
                    }
                }
            }
            $this->ajaxReturn($goodsList);
        }else{
            $id=I('get.bid',0,'intval');
            $bwhere=array('id'=>$id);
            $bname=D('Enterprise','Service')->getBlandField($bwhere,'brandname');//页面显示品牌标题
            $swhere=array('bid'=>$id,'gstatus'=>1);
            $goods=D('Enterprise','Service')->getGoodsSelect($swhere,$type,'create_time desc',1);//显示商品
            $goodsList=array();
            foreach ($goods as $v){//遍历把商铺下的商品取出;
                foreach ($shops as $v1){
                    if($v['sid']==$v1['sid'] && ($v1['theme']=='Hotel' || $v1['theme']=='ktv')){
                        $v['theme'] = 'index';
                        $goodsList[]=$v;
                    }else if($v['sid']==$v1['sid']){
                        $goodsList[]=$v;
                    }
                }
            }
            $this->assign('goods',$goodsList);
            $this->assign('bname',$bname);
            $this->assign('bid',$id);
            $this->newdisplay();
        }
    }
    
    //企业版商品分类页
    public function shopClass(){
        $enter=D('Enterprise','Service');
        $id=I('id',0,'intval');
        $jid=$this->jid;
        $swhere['jid']=$jid;
        $swhere['gstatus']=1;
        //查询该企业版及城市下的店铺
        $shops=$this->getShopsCity('sid,theme');
        //广告投放
        $where=array('jid'=>$this->jid,'sid'=>0,'status'=>1);
        $type='ad_img,ad_type,ad_position';
        $order='addTime desc';
        $adtop=$enter->getAdmanage($where,$type,$order,$id,1);//顶部广告
        $this->assign('adtop',$adtop);
        $admiddle=$enter->getAdmanage($where,$type,$order,$id,2);//中部广告
        $this->assign('admiddle',$admiddle);
        $addown=$enter->getAdmanage($where,$type,$order,$id,3);//底部广告
        $this->assign('addown',$addown);
        $adxuanfu=$enter->getAdmanage($where,$type,$order,$id,0,4);//悬浮广告
        $adxuanfu['ad_position']=$adxuanfu['ad_position'][0];
        $this->assign('adxuanfu',$adxuanfu);
        //查询该城市下的店铺
        if($_POST['id']){
            $array=$this->getColumnOrder($_POST);
            $goodsList=array();
            foreach ($array['goodsList'] as $k => $v){//遍历把商铺下的商品取出;
                foreach ($shops as $v1){
                    if($v['sid']==$v1['sid'] && ($v1['theme']=='Hotel' || $v1['theme']=='ktv')){
                        $v['theme'] = 'index';
                        $goodsList[]=$v;
                    }else if($v['sid']==$v1['sid']){
                        $goodsList[]=$v;
                    }
                }
            }
            $array['goodsList']=$goodsList;
            $this->ajaxReturn($array);
        }else{
            if(!$id){$this->display('Error:404');exit;}
            //显示导航TOP行业分类
            $where=array('status'=>1);
            $indname=$enter->getIndFind("id=$id",'id,ind_name');//查询该分类的内容
            $indarray=$enter->getIndustry($where,'ind_order desc',$id);//查询子分类
            $this->assign('indname',$indname);
            $this->assign('indarray',$indarray);
            //商品列表显示
            $indid=$indname['id'].',';
            foreach($indarray as $v){
                $indid.=$v['id'].',';
                $indson=$enter->getIndustry($where,'ind_order desc',$v['id']);//查询子分类的id
                foreach ($indson as $v1){
                    $indid.=$v1['id'].',';
                }
            }
            $iid=trim($indid,',');
            $type='gid,gname,sid,goprice,gdprice,gstock,gimg,gsales,sale_num,ind_id';
            $swhere['ind_id']=array('in',$iid);
            $goodsArr=$enter->getGoodsSelect($swhere,$type,'create_time desc',$page);
            $goodsList=array();
            foreach ($goodsArr as $k => $v){//遍历把商铺下的商品取出;
                foreach ($shops as $v1){
                if($v['sid']==$v1['sid'] && ($v1['theme']=='Hotel' || $v1['theme']=='ktv')){
                        $v['theme'] = 'index';
                        $goodsList[]=$v;
                    }else if($v['sid']==$v1['sid']){
                        $goodsList[]=$v;
                    }
                }
            }
            $this->assign('goodsList',$goodsList);
            $this->newdisplay();
        }
    }
    //企业版搜索页面
    public function searchAll(){
        $enter=D('Enterprise','Service');
        if($_POST['searchtype']){//判断搜索类型
            if($_POST['searchkey']){//关键字
                $key=$this->replace_specialChar($_POST['searchkey']);//去掉特殊符号
                $searchwhere['jid']=array('eq',$this->jid);
                $searchwhere['searchkey']=array('like',$key.'%');
                $searchhot=$enter->getSearchHot($searchwhere,$_POST['searchtype'],10);//查询结果显示10条
                $this->ajaxReturn($searchhot);
            }
            $hotwhere=array('jid'=>$this->jid);
            $search['searchhot']=$enter->getSearchHot($hotwhere,$_POST['searchtype'],8);//热门搜索
            $arr=json_decode($_COOKIE['mysearch']);
            $str='';
            foreach ($arr as $v){
                $str.=$v[0].',';
            }
            $idstr=trim($str,',');
            $mywhere['id']=array('in',$idstr);
            $mywhere['jid']=$this->jid;
            $search['mysearch']=$enter->getSearchHot($mywhere,$_POST['searchtype'],8);//历史搜索
            $this->ajaxReturn($search);
        }
        if($_POST['clear']){//清空历史搜索记录
            cookie('mysearch',null);
            exit('1');
        }
        $this->newdisplay();
    }
    //企业版商品页面
    public function goodsList(){
        $enter=D('Enterprise','Service');
        $jid=$this->jid;
        $sidArr = $this->getShopsCity('sid');
        $sidStr = '';
        foreach ($sidArr as $v){
            $sidStr .= $v['sid'].',';
        }
        $sidStr = trim($sidStr,',');
        if($_POST['id']){
            $id=(int)$_POST['id'];
            //显示第一个一级分类下的二级分类
            $ind=$enter->getGoodsIndustry('status=1','id,ind_name,cateid',$id,$jid);
            foreach ($ind as $k=>$v){
                if($v['next'][0]['img']){
                    $ind[$k]['imgIf'] = 1;
                }else{
                    $ind[$k]['imgIf'] = 0;
                }
            }
            $this->ajaxReturn($ind);
        }
        $shopInd=D('Enterprise','Service')->getShopselect(array('jid'=>$this->jid,'status'=>'1','is_show'=>1),'ind_id,sname,sid');
        $shopInd=array_unique(array_column($shopInd,'ind_id'));
        foreach ($shopInd as $k=>$v){
            if($v == 0){
                unset($shopInd[$k]);
            }
        }
        $indStr=implode($shopInd,',');
        //调取一级分类
        $indone=$enter->getGoodsIndustry(array('id'=>array('in',$indStr),'cateid'=>0,'status'=>1),'id,ind_name,cateid');
        //显示第一个一级分类下的二级分类
        $indtwo=$enter->getGoodsIndustry('status=1','id,ind_name,cateid',$indone[0]['id'],$jid,$sidStr);
        foreach ($indtwo as $k=>$v){
            if($v['next'][0]['img']){
                $indtwo[$k]['imgIf'] = 1;
            }else{
                $indtwo[$k]['imgIf'] = 0;
            }
        }
        //显示第一个一级分类下的二级分类的三级分类
        $this->assign('indtwo',$indtwo);
        $this->assign('indone',$indone);
        $this->assign('page_current','goods');
        $this->newdisplay();
        
    }
    //企业版商品列表
    public function goodsListAll(){
		$enter=D('Enterprise','Service');
        $id=I('id',0,'intval');
        $jid=$this->jid;
        $swhere['jid']=$jid;
        $swhere['gstatus']=1;
        //广告投放
        $where=array('jid'=>$this->jid,'sid'=>0,'status'=>1);
        $type='ad_img,ad_type,ad_position';
        $order='addTime desc';
        $adid='goods';
        $adtop=$enter->getAdmanage($where,$type,$order,$adid,1);//顶部广告
        $this->assign('adtop',$adtop);
        $admiddle=$enter->getAdmanage($where,$type,$order,$adid,2);//中部广告
        $this->assign('admiddle',$admiddle);
        $addown=$enter->getAdmanage($where,$type,$order,$adid,3);//底部广告
        $this->assign('addown',$addown);
        $adxuanfu=$enter->getAdmanage($where,$type,$order,$adid,0,4);//悬浮广告
        $adxuanfu['ad_position']=$adxuanfu['ad_position'][0];
        $this->assign('adxuanfu',$adxuanfu);
        $shops=$this->getShopsCity('sid,theme');
        $sids = array_column($shops, 'sid');
        $sstr= implode(',',$sids);
        $swhere['sid']=array('in',$sstr);
        $swhere['ind_id']=array('eq',$id);
        if($_POST['id']){
            $array=$this->getColumnOrder($_POST);
            foreach ($array['goodsList'] as $k=>$v){
                foreach ($shops as $v1){
                    if($v['sid'] == $v1['sid'] && ($v1['theme'] == 'Hotel' || $v1['theme'] == 'ktv')){
                        $array['goodsList'][$k]['theme'] = 'index';
                    }
                }
            }
            $this->ajaxReturn($array);
        }else{
            if(!$id){$this->display('Error:404');exit;}
            //显示当前进入页面时的三级分类
            $ind=D('Industry_class','Data')->find(array('id'=>$id),'id,ind_name,cateid');//三级
            $this->assign('ind',$ind);
            //显示当前进入页面时的二级
            $upid=explode('-',$ind['cateid']);
            $upind=D('Industry_class','Data')->find(array('id'=>$upid[1]),'id,ind_name,cateid');//二级
            //显示当前进入页面时的一级
            $this->assign('upid',$upid[0]);
            $this->assign('upind',$upind);
            //调取所有二级分类
            $indList=$enter->getIndustry(array('status'=>1),'ind_order desc',$upid[0]);
            $this->assign('indarray',$indList);
            //显示当前进入页面时的二级下的全部三级分类
            $inddown=$enter->getIndustry(array('status'=>1),'ind_order desc',$upind['id']);
            $this->assign('inddown',$inddown);
            $type='gid,gname,sid,goprice,gdprice,gstock,gimg,sale_num,gsales,ind_id';
            $goodsList=$enter->getGoodsSelect($swhere,$type,'create_time desc');
            foreach ($goodsList as $k=>$v){
                foreach ($shops as $v1){
                    if($v['sid'] == $v1['sid'] && ($v1['theme'] == 'Hotel' || $v1['theme'] == 'ktv')){
                        $goodsList[$k]['theme'] = 'index'; 
                    }
                }
            }
//             //判断商品是否是优惠活动
//             $auth = M('merchant')->field('active_auth,invest_auth,limit_auth')->where(array('jid'=>$jid))->find();
//             if($auth['active_auth'] == 1){
//                 $active = M('specialoffers')->where(array('jid'=>$jid,'sid'=>0,'yh_state'=>array('eq',1)))->select();
//             }else{
//                 $active = M('specialoffers')->where(array('sid'=>array('in',$sids),'yh_state'=>array('eq',1)))->select();
//             }
//             //判断商品是否是消费投资
//             if($auth['invest_auth']==1){
//                 $invest = M('touzi')->where(array('jid'=>$jid,'sid'=>0,'status'=>array('eq',1)))->select();
//             }else{
//                 $invest = M('touzi')->where(array('sid'=>array('in',$sids),'status'=>array('eq',1)))->select();
//             }
//             //判断商品是否是限时限量购
//             if($auth['limit_auth']==1){
//                 $limit = M('limit_goods')->where(array('jid'=>$jid,'sid'=>0,'status'=>array('eq',1),'lstock'=>array(array('eq',-1),array('gt',0),'or')))->select();
//             }else{
//                 $limit = M('limit_goods')->where(array('sid'=>array('in',$sids),'status'=>array('eq',1),'lstock'=>array(array('eq',-1),array('gt',0),'or')))->select();
//             }
//             foreach ($goodsList as $k=>$v){
//                 foreach ($active as $v1){//优惠活动
//                     if($v['gid']==$v1['gid']){
//                         $goodsList[$k]['active']='active';
//                     }
//                 }
//                 foreach ($invest as $v1){//消费投资
//                     if($v1['sid']){//有sid的情况
//                         if($v1['gid']){//有gid的情况
//                             $gidArr = explode(',',$v1['gid']);
//                             foreach ($gidArr as $v2){
//                                 if($v2==$v['gid']){
//                                     $goodsList[$k]['invest']='invest';
//                                 }
//                             }
//                         }else{
//                             if($v['sid']==$v1['sid']){
//                                 $goodsList[$k]['invest']='invest';
//                             }
//                         }
//                     }else{
//                         if($v1['gid']){//有gid的情况
//                             $gidArr = explode(',',$v1['gid']);
//                             foreach ($gidArr as $v2){
//                                 if($v2==$v['gid']){
//                                     $goodsList[$k]['invest']='invest';
//                                 }
//                             }
//                         }else{
//                             $goodsList[$k]['invest']='invest';
//                         }
//                     }
//                 }
//                 foreach ($limit as $v1){//限时限量购
//                     if($v1['gid']==$v['gid']){
//                         $goodsList[$k]['limit']='limit';
//                     }
//                 }
//             }
//             var_dump($goodsList);die;
            $this->assign('goodsList',$goodsList);
            $this->newdisplay();
        }
    }
    
    //企业版商家列表
    public function shopList(){
        $jid=$this->jid;
        $enter=D('Enterprise','Service');
        $point=$this->point;
        $cityid=(int)cookie('cityid');
        //广告投放
        $where=array('jid'=>$this->jid,'sid'=>0,'status'=>1);
        $type='ad_img,ad_type,ad_position';
        $order='addTime desc';
        $adid='shops';
        $adtop=$enter->getAdmanage($where,$type,$order,$adid,1);//顶部广告
        $this->assign('adtop',$adtop);
        $admiddle=$enter->getAdmanage($where,$type,$order,$adid,2);//中部广告
        $this->assign('admiddle',$admiddle);
        $addown=$enter->getAdmanage($where,$type,$order,$adid,3);//底部广告
        $this->assign('addown',$addown);
        $adxuanfu=$enter->getAdmanage($where,$type,$order,$adid,0,4);//悬浮广告
        $adxuanfu['ad_position']=$adxuanfu['ad_position'][0];
        $this->assign('adxuanfu',$adxuanfu);
        if(IS_POST){
            $id=(int)$_POST['id'];
            $shoplist=$enter->getColumnShop($jid,$cityid,$point,$_POST);
            //查看是否已经收藏
            $cwhere=array(
                'jid'=>$jid,
                'mid'=>$this->mid,
                'ctype'=>2
            );
            $cshops = D('Collect','Service')->getShopCollect($cwhere,'sid');//查看是否已经收藏
            foreach ($shoplist as $k=>$v){
                foreach ($cshops as $v1){
                    if($v['sid'] == $v1['sid']){
                        $shoplist[$k]['shouchang']=1;
                    }
                }
            }
            $this->ajaxReturn($shoplist);
        }
        //显示全部一级分类
        $where=array('jid'=>$jid,'i.status'=>1,'cateid'=>0,'s.status'=>'1','is_show'=>1);
        if($cityid != 100000){
            $where['city']=$cityid;
        }
        $indone=$enter->getIndustryShopsNum($where);
        $this->assign('indone',$indone);
        //查询显示店铺信息
        $where=array('s.jid'=>$jid,'is_show'=>'1','s.status'=>'1');
        if($cityid != 100000){
            $where['city']=$cityid;
        }
        $field='s.sid,sname,lng,lat,logo,ind_id,theme,SUM(grade) AS grade_num,COUNT(e.id) AS id_num';
		$shoplist = $enter->getShopList($where,$field);
		//查看是否已经收藏
		$cwhere=array(
		    'jid'=>$jid,
		    'mid'=>$this->mid,
		    'ctype'=>2
		);
		$cshops = D('Collect','Service')->getShopCollect($cwhere,'sid');
		foreach ($shoplist as $k=>$v){
		    foreach ($cshops as $v1){
		        if($v['sid'] == $v1['sid']){
		            $shoplist[$k]['shouchang']=1;
		        }
		    }
		}
		//计算和我的距离
		if($point){
		    foreach ($shoplist as $k=>$v){
		        $dis[$k]=D('Distance')->getDistance($point, $v['lat'], $v['lng']);
		    }
		    foreach ($shoplist as $k=>$v){
		        $distance=D('Distance')->getDistance($point, $v['lat'], $v['lng']);
		        if($distance/1000 > 1){
		            $shoplist[$k]['distance']=round($distance/1000,1).'公里';
		        }else{
		            $shoplist[$k]['distance']=$distance.'米';
		        }
		    }
		    array_multisort($dis,SORT_ASC,$shoplist);
		}else{
		    $point = 1;
		    $this->assign('point',$point);
		}
		$this->assign('page_current','shops');
		$this->assign('shoplist',$shoplist);
        $this->newdisplay();
    }
    
    //企业版城市列表
    public function cityList(){
        $enter=D('Enterprise','Service');
        if(IS_POST){
            $aid=(int)$_POST['aid'];
            if($aid != cookie('cityid')){
                cookie('cityid',$aid,1000000);
            }
            $where['aid']=$aid;
            $re=D('Address','Data')->setInc($where,'city_hot');
            exit($re?'1':'2');
        }
        //热门城市
        $hotlist=D('Address','Data')->select('','aid,aname','city_hot desc','8');
        $this->assign('hotlist',$hotlist);
        //显示当前城市
        $cityID['aid']=(int)cookie('cityid');
        $mycity=$enter->getAddressFind($cityID,'aid,aname');
        $this->assign('mycity',$mycity);
        $cityArr=$enter->getCityList($type);//查询出全部城市
        $citylist=array();
        for($i='A' ; $i<='Z' ;$i++){//按照首字母重新拼装数组
            foreach ($cityArr as $v){
                if($v['head'] == $i){
                    $citylist[$i][]=$v;
                }
            }
        }
        $this->assign('citylist',$citylist);
        $this->newdisplay();
    }
    
    //企业版行业分类更多列表
    public function industrymore(){
        $jid=$this->jid;
        $where=array('jid'=>$jid,'status'=>1);
        $industry=D('Enterprise','Service')->getIndustryCol($where,$type,$order);
        $this->assign('industry',$industry);
        $this->newdisplay();
    }
    
    //企业版商户注册页面
    public function register(){
        //取出行业分类
        $industry_class = M('industry_class')->where(array('cateid'=>0))->select();
        $this->assign('industry_class',$industry_class);
        if($_POST){
            //接收注册信息
            $data = array();
            $data['jid'] = $this->jid;
            $data['shopname'] = I('post.shopname','');
            $data['logo'] = I('post.logo','');
            $data['industry_class'] = I('post.industry_class','');
            $data['realname'] = I('post.realname','');
            $data['idcard'] = I('post.idcard','');
            $data['tel'] = I('post.tel','');
            $data['address'] = I('post.address','');
            $data['addressdetail'] = I('post.addressdetail','');
            $data['account_name'] = I('post.account_name','');
            $data['account_pwd'] = I('post.account_pwd','');
            $url = $this->upload();
            //接收不需要裁剪图片
            $idcardfront = $url['idcardfront'];
            $idcardback = $url['idcardback'];
            $license = $url['license'];
            $data['idcardfront'] =$idcardfront;
            $data['idcardback'] =$idcardback;
            $data['license'] =$license;
            //店铺保证金
            $margin = M('apply_shop')->where(array('jid'=>$this->jid))->getField('margin');
            $data['margin'] = $margin;
            //店铺使用金额
            $amount = M('apply_shop')->where(array('jid'=>$this->jid))->getField('amount');
            $data['amount'] = $amount;
            //总共支付金额
            $data['pay_price'] = $margin + $amount;
            $data['addtime'] = time();
            //var_dump($data);exit;
            //先入库然后跳转到支付页面
            $apply_shop = D('ApplyShop','Data');
            $id = $apply_shop->add($data);
            if($id){
                $this->redirect("/Enterprise/pay/id/".$id,0);
            }else{
                //echo M()->getLastSql();exit;
                $this->error("资料提交失败，请再次确认...");
            };
        }
        $this->newdisplay();
    }
    //企业版商户审核失败修改资料
    public function register_edit(){
        $jid = $this->jid;
        $id = I('get.id',0);
        $row = D('ApplyShop','Data')->get_one(array('id'=>$id,'jid'=>$jid));
        //var_dump($row);exit;
        $this->assign('row',$row);
        $this->newdisplay();
    }
    public function register_editok(){
        $jid = $this->jid;
        $id = I('post.id',0);
        //接收注册信息
        $data = array();
        $data['id'] = $id;
        $data['jid'] = $jid;
        $data['shopname'] = I('post.shopname','');
        $data['logo'] = I('post.logo','');
        $data['industry_class'] = I('post.industry_class','');
        $data['realname'] = I('post.realname','');
        $data['idcard'] = I('post.idcard','');
        $data['tel'] = I('post.tel','');
        $data['address'] = I('post.address','');
        $data['addressdetail'] = I('post.addressdetail','');
        $data['account_name'] = I('post.account_name','');
        $data['account_pwd'] = I('post.account_pwd','');
        //接收不需要裁剪图片
        $idcardfront = I('post.imgFile1','');
        $idcardback = I('post.imgFile2','');
        $license = I('post.imgFile3','');
        //用upload方法生成图片地址
        $url = $this->upload();
        $idcardfront = $url['idcardfront'];
        $idcardback = $url['idcardback'];
        $license = $url['license'];
        $data['idcardfront'] = $idcardfront;
        $data['idcardback'] = $idcardback;
        $data['license'] = $license;
        //如果没有更新图片则从原数据中取出
        if($idcardfront == null){
            $row = D('ApplyShop','Data')->get_one(array('id'=>$id,'jid'=>$jid));
            $data['idcardfront'] = $row['idcardfront'];
        }
        if($idcardback == null){
            $row = D('ApplyShop','Data')->get_one(array('id'=>$id,'jid'=>$jid));
            $data['idcardback'] = $row['idcardback'];
        }
        if($license == null){
            $row = D('ApplyShop','Data')->get_one(array('id'=>$id,'jid'=>$jid));
            $data['license'] = $row['license'];
        }
        $row = D('ApplyShop','Data')->get_one(array('id'=>$id,'jid'=>$jid));
        $data['pay_price'] = $row['pay_price'];
        $data['pay_type'] = $row['pay_type'];
        $data['use_time'] = $row['use_time'];
        $data['status'] = 0;
        $data['addtime'] = time();
        $save = D('ApplyShop','Data');
        if($save->save(array('id'=>$id),$data)){
            if($data['pay_type'] == 0){
                $this->redirect("/Enterprise/pay/id/".$id,0);
            }else{
                $this->success('修改成功,请等待审核!','/Enterprise/index',1);
            }
        }else{
//            echo M()->getLastSql();exit;
            $this->error('更新失败',"/Enterprise/register_edit/id/".$id,0);
        }
    }
    //店铺logo生成路径(裁剪)
    public function upTX(){
        $base64_image_content = I('tx','');
        $u = upload_base_img($base64_image_content);
        if($u){
            $this->ajaxReturn($u);
        }
    }
    //搜索店铺帐号是否存在，如存在则不能再申请
    public function search(){
        $jid = $this->jid;
        $account_name = I("post.account_name");
        if($account_name){
                $res = M('apply_shop')->where(array('jid'=>$jid))->field('account_name')->select();
                $account_names = array_column($res,'account_name');
            if(in_array($account_name,$account_names)){
                $this->ajaxReturn('1');
            }else{
                $this->ajaxReturn('2');
            }
        }
    }
    //支付页面
    public function pay(){
        //判断支付宝支付还是微信支付
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            $pay_type = 'weixin';
        } else {
            $pay_type = 'alipay';
        }
        $id = I('get.id','');
        $row = D('ApplyShop','Data')->find(array('id'=>$id));
        $pay_price = $row['pay_price'];
        $this->assign('pay_price',$pay_price);
        //var_dump($pay_price);exit;
        $jid = $this->jid;
        $this->assign('id',$id);
        if($_POST) {
            //拼装订单表的数据
            $id = I('post.id',0);
            $pay_price = I('post.pay_price',0);
            $oid = orderNumber();
            $order = M('order');
            $opt = array(
                'o_id'  =>$oid,
                'apply_shop_id'=>$id,
                //'apply_pay_type' =>$apply_pay_type,
                'o_sid' => 0,
                'o_jid' => $jid,
                'o_uid' => 0,
                'o_type' => 2,
                'o_name' => '',
                'o_phone' => '',
                'o_address' => '',
                'o_category'=>'',
                'o_seat' => '',
                'o_dstime' => date("Y-m-d H:i:s"),
                'o_dstatus' => 1,
                'o_pstatus' => 0,
                'o_gtype' => 'merchant_shop',
                'o_table'   => '',
                'o_remarks'   => '',
                'o_xftype'   => '',
                'o_pway'   => '',
                'o_psf'   => '',
                'o_discount'   => '',
                'o_total' => $pay_price,
                'o_price' => $pay_price,
            );
            //var_dump($opt);exit;
            $order->add($opt);
            $data = array("msg" => "true");
            if ($pay_type == 'alipay' && $pay_price > 0) {
                $data = array('msg' => 'pay', 'url' => U('Pay/request_alipay', array('o_id' => $oid, 'jump' => 1)));
            } elseif ($pay_type == 'weixin' && $pay_price > 0) {
                $data = array('msg' => 'pay', 'url' => U('/Wechat/dsWxJsPay@ho') . '?o_id=' . $oid . '&jump=1');
            }
            $this->ajaxReturn($data);
        }
        $this->newdisplay();
    }

    //验证码
    public function verify() {

        $verify = new \Think\Verify(array(

            'fontSize'			=> I('get.fontSize', 15, 'intval'),

            'imageH'			=> I('get.height', 25, 'intval'),

            'imageW'			=> I('get.width', 59, 'intval'),

            'length'			=> I('get.lenth', 4, 'intval'),

            'useNoise'			=> false,

            'fontttf'			=> '7.ttf',

            'codeSet'			=> '1234567890123456789012345678901234567890'

        ));

        $verify->entry('loginVerify');

    }
    //登录查看状态
    public function checklogin(){
        $verify = new \Think\Verify();
        if(! $verify->check($_POST['verify'], 'loginVerify')) {$data=8;echo $data;exit;}
        $username = $_POST['username'];
        $password = $_POST['password'];
        //帐号或密码错误
        if(empty($username) || empty($password)){
            $data=9;
            echo $data;exit;
        }
        //账号查询待优化
        $apply = D('ApplyShop','Data')->find(array('account_name'=>$username,'account_pwd'=>$password));
        $id = $apply['id'];
        //帐号密码正确
        if($apply['account_pwd'] == $password && $apply['account_name'] == $username){
            //已支付等待审核
            if($apply['status'] == 0 && $apply['pay_type']== 1){
                $arr = array();
                $arr['id'] = $id;
                $arr['redit'] = 1;
                $arr['account_name'] = $apply['account_name'];
                $arr['account_pwd'] = $apply['account_pwd'];
                $this->ajaxReturn($arr);
            }
            //已支付审核通过
            if($apply['status'] == 1 && $apply['pay_type']== 1){
                $arr = array();
                $arr['id'] = $id;
                $arr['redit'] = 2;
                $arr['account_name'] = $apply['account_name'];
                $arr['account_pwd'] = $apply['account_pwd'];
                $this->ajaxReturn($arr);
            }
            //已支付审核失败
            if($apply['status'] == 2 && $apply['pay_type']== 1){
                $arr = array();
                $arr['id'] = $id;
                $arr['redit'] = 3;
                $arr['apply_id'] = $id;
                $this->ajaxReturn($arr);
            }
            //未支付等待审核
            if( $apply['pay_type']== 0 && $apply['status'] == 0){
                $arr = array();
                $arr['id'] = $id;
                $arr['redit'] = 4;
                $arr['account_name'] = $apply['account_name'];
                $arr['account_pwd'] = $apply['account_pwd'];
                $this->ajaxReturn($arr);
            }
            //未支付审核通过
            if( $apply['pay_type']== 0 && $apply['status'] == 1){
                $arr = array();
                $arr['id'] = $id;
                $arr['redit'] = 5;
                $arr['account_name'] = $apply['account_name'];
                $arr['account_pwd'] = $apply['account_pwd'];
                $this->ajaxReturn($arr);
            }
            //未支付审核失败
            if( $apply['pay_type']== 0 && $apply['status'] == 2){
                $arr = array();
                $arr['id'] = $id;
                $arr['redit'] = 6;
                $arr['account_name'] = $apply['account_name'];
                $arr['account_pwd'] = $apply['account_pwd'];
                $this->ajaxReturn($arr);
            }
        }else{$data=9;echo $data;exit;}
    }
    
    //企业版营销活动
    public function marketing(){
        $enter=D('Enterprise','Service');
        $jid=$this->jid;
        $cityid=cookie('cityid');
        $point=$this->point;
        $code=I('code','','string');//活动类型
        if($code=='activity')$code='active';
        //广告投放
        $where=array('jid'=>$this->jid,'sid'=>0,'status'=>1);
        $type='ad_img,ad_type,ad_position';
        $order='addTime desc';
        $adtop=$enter->getAdmanage($where,$type,$order,$code,1);//顶部广告
        $this->assign('adtop',$adtop);
        $admiddle=$enter->getAdmanage($where,$type,$order,$code,2);//中部广告
        $this->assign('admiddle',$admiddle);
        $addown=$enter->getAdmanage($where,$type,$order,$code,3);//底部广告
        $this->assign('addown',$addown);
        $adxuanfu=$enter->getAdmanage($where,$type,$order,$code,0,4);//悬浮广告
        $adxuanfu['ad_position']=$adxuanfu['ad_position'][0];
        $this->assign('adxuanfu',$adxuanfu);
        $Activity=D('Enterprise','Service')->getModulecontent($jid,'','activity');//获取活动页面内容
        foreach ($Activity[0]['en_module_content'] as $k => $v){//查看设置的显示的店铺
            if($code=='active')$code='activity';
            if($v['active']==$code){
                $markArr=$v;
                $sidStr=implode($v['sid'],',');
            }    
        }
        if($code=='activity')$code='active';
        if(IS_POST){
            $id=(int)$_POST['id'];
            $code=array('code'=>$code,'sid'=>$sidStr);
            $shoplist=D('Enterprise','Service')->getColumnShop($jid,$cityid,$point,$_POST,'',$code);
            $this->ajaxReturn($shoplist);
        }
        $where=array(
            's.jid'=>$jid,
            'is_show'=>1,
            's.sid'=>array('in',$sidStr),
            's.status'=>'1'
        );
        if($cityid != 100000){
            $where['city']=$cityid;
        }
        $field='s.jid,s.sid,sname,lng,lat,logo,ind_id,SUM(grade) AS grade_num,COUNT(e.id) AS id_num';
        $shop_list=D('Enterprise','Service')->getShopGrade($where,$field,$code,$point);//获取开启活动的商户
        if(!$point){
            $point = 1;
            $this->assign('point',$point);
        }
        //显示全部一级分类
        $sid='';
        foreach ($shop_list as $v){
            $sid.=$v['sid'].',';
        }
        $indwhere=array('jid'=>$jid,'i.status'=>1,'cateid'=>0,'s.sid'=>array('in',trim($sid,',')));
        if($cityid != 100000){
            $indwhere['city']=$cityid;
        }
        $indone=D('Enterprise','Service')->getIndustryShopsNum($indwhere);
        //标题
        $merchant=$enter->MerFind(array('jid'=>$jid),'mnickname');
        $this->assign('merchant',$merchant);
        $this->assign('indone',$indone);
        $this->assign('shop_list',$shop_list);
        $this->assign('markArr',$markArr);
        $this->newdisplay();
    }

    //企业版通知消息页
    public function notice(){
        $id=I('get.id',0,'intval');
        if($id>0){
            $where="id=$id and jid=$this->jid and sid=-1 and show_notice=0";
            $notice=D('Enterprise','Service')->NoticeFind($where,$type);
            $this->assign('notice',$notice?$notice:'');
            $this->newdisplay();
        }else{
            $this->display('Error:404');
            exit;
        }
    }
    
    //企业版商品搜索展示页面
    public function goodsSearch(){
        $enter=D('Enterprise','Service');
        $_GET['searchkey']=$swhere['gname']=$this->replace_specialChar($_GET['keyword']);//去掉特殊符号
        $_GET['id']=(int)$_GET['searchid'];unset($_GET['searchid']);
        $_GET['addtime']=time();
        $_GET['jid']=$swhere['jid']=$this->jid;
        $_GET['searchtype']='goods';
        $shops=$this->getShopsCity('sid,theme');
        $sidArr = array_column($shops,'sid');
        $sstr = implode(',',$sidArr);
        $swhere['sid']=array('in',$sstr);
        $swhere['ind_id']=array('neq',0);
        if(IS_POST){
            $swhere['gname']=$this->replace_specialChar($_POST['keyword']);
            $array=$this->getColumnOrder($_POST,$swhere);
            foreach ($array['goodsList'] as $k=>$v){
                foreach ($shops as $v1){
                    if($v['sid'] == $v1['sid'] && ($v1['theme'] == 'Hotel' || $v1['theme'] == 'ktv')){
                        $array['goodsList'][$k]['theme'] = 'index';
                    }
                }
            }
            $this->ajaxReturn($array);
        }
        //调取所有二级分类
        $indList=$enter->getIndustry(array('status'=>1),'ind_order desc');
        $this->assign('indarray',$indList);
        $re=$enter->setSearchHotAdd($_GET);//储存搜索数据
        if(!$re){$this->display('Error:404');}
        $type='gid,gname,sid,goprice,gdprice,gstock,gimg,sale_num,gsales,ind_id';
        $goodsList=$enter->getGoodsSearch($swhere,$type,'create_time desc');
        foreach ($goodsList as $k=>$v){
            foreach ($shops as $v1){
                if($v['sid'] == $v1['sid'] && ($v1['theme'] == 'Hotel' || $v1['theme'] == 'ktv')){
                    $goodsList[$k]['theme'] = 'index';
                }
            }
        }
        $this->assign('goodsList',$goodsList);
        $this->newdisplay();
    }
    
    //企业版店铺搜索展示页面
    public function shopsSearch(){
        $enter=D('Enterprise','Service');
        $jid=$this->jid;
        $point=$this->point;
        $cityid=cookie('cityid');
        if(IS_POST){
            $id=(int)$_POST['id'];
            $keyword=$this->replace_specialChar($_POST['keyword']);
            unset($_POST['keyword']);
            $shoplist=$enter->getColumnShop($jid,$cityid,$point,$_POST,$keyword);
            $this->ajaxReturn($shoplist);
        }
        //拼装数组
        $_GET['searchkey']=$keyword=$this->replace_specialChar($_GET['keyword']);//去掉特殊符号
        $_GET['id']=(int)$_GET['searchid'];unset($_GET['searchid']);
        $_GET['addtime']=time();
        $_GET['jid']=$this->jid;
        $_GET['searchtype']='shops';
        $swhere['jid']=$jid;
        $re=$enter->setSearchHotAdd($_GET);//搜索关键字+1
        if(!$re){$this->display('Error:404');}
        //显示全部一级分类
        $where=array(
            'jid'=>$jid,
            'i.status'=>1,
            'cateid'=>0,
            'sname'=>array('like','%'.$keyword.'%')
        );
        if($cityid != 100000){
            $where['city']=$cityid;
        }
        $indone=$enter->getIndustryShopsNum($where);
        $this->assign('indone',$indone);
        //查询显示店铺信息
        $where=array(
            's.jid'=>$jid,
            'is_show'=>'1',
            'sname'=>array('like','%'.$keyword.'%')
        );
        if($cityid != 100000){
            $where['city']=$cityid;
        }
        $field='s.sid,sname,lng,lat,logo,ind_id,theme,SUM(grade) AS grade_num,COUNT(e.id) AS id_num';
        $shoplist = $enter->getShopList($where,$field);
        //计算和我的距离
		if($point){
		    foreach ($shoplist as $k=>$v){
		        $dis[$k]=D('Distance')->getDistance($point, $v['lat'], $v['lng']);
		    }
		    foreach ($shoplist as $k=>$v){
		        $distance=D('Distance')->getDistance($point, $v['lat'], $v['lng']);
		        if($distance/1000 > 1){
		            $shoplist[$k]['distance']=round($distance/1000,1).'公里';
		        }else{
		            $shoplist[$k]['distance']=$distance.'米';
		        }
		    }
		    array_multisort($dis,SORT_ASC,$shoplist);
		}else{
		    $point = 1;
		    $this->assign('point',$point);
		}
        $this->assign('shoplist',$shoplist);
        $this->newdisplay();
    }
    
    //收藏店铺
    public function collect() {
        $enter=D('Enterprise','Service');
        $jid=$this->jid;
        $point=$this->point;
        $mid=$this->mid;
        $shops = $this->getShopsCity('sid,theme');
        if(!$mid){
            redirect(U('User/login',array('sid'=>$this->sid,'jid'=>$this->jid,'backurl'=>url_param_encrypt(U('Enterprise/index'),'E'),'returnurl'=>url_param_encrypt(U(),'E'))));
            exit;
        }
        //查询显示店铺信息
        $where=array('s.jid'=>$jid,'is_show'=>'1','s.status'=>'1');
        $field='s.sid,sname,lng,lat,logo,ind_id,theme,SUM(grade) AS grade_num,COUNT(e.id) AS id_num';
        $shoplist = $enter->getShopList($where,$field);
        //查询我店铺的收藏
        $cwhere=array(
            'jid'=>$jid,
            'mid'=>$mid,
            'ctype'=>2
        );
        $cshops = D('Collect','Service')->getShopCollect($cwhere,'sid');
        $shopArr=array();
        foreach ($cshops as $v){
            foreach ($shoplist as $v1){
                if($v['sid']==$v1['sid']){
                    $shopArr[]=$v1;
                }
            }
        }
        $shoplist=$shopArr;
        //计算和我的距离
        if($point){
            foreach ($shoplist as $k=>$v){
                $distance=D('Distance')->getDistance($point, $v['lat'], $v['lng']);
                if($distance/1000 > 1){
                    $shoplist[$k]['distance']=round($distance/1000,1).'公里';
                }else{
                    $shoplist[$k]['distance']=$distance.'米';
                }
            }
        }
        $this->assign('shoplist',$shoplist);
        $this->newdisplay();
    }
    
    //收藏宝贝
    public function goodcollect(){
        $enter=D('Enterprise','Service');
        $jid=$this->jid;
        $mid=$this->mid;
        if(!$mid){
            redirect(U('User/login',array('sid'=>$this->sid,'jid'=>$this->jid,'backurl'=>url_param_encrypt(U('Enterprise/index'),'E'),'returnurl'=>url_param_encrypt(U(),'E'))));
            exit;
        }
        //商品的收藏
        $gwhere=array(
            'jid'=>$jid,
            'mid'=>$mid,
            'ctype'=>1
        );
        $cgoods = D('Collect','Service')->getShopCollect($gwhere,'gid');
        $str='';
        foreach ($cgoods as $v){
            $str.=$v['gid'].',';
        }
        $str=trim($str,',');
        $type='gid,gname,sid,goprice,gdprice,gstock,gimg,gsales,sale_num,ind_id';
        $swhere['gid']=array('in',$str);
        $goodsList=$enter->getGoodsSelect($swhere,$type,'create_time desc',$page);
        //查看是否是酒店和KTV店铺
        $shops = $this->getShopsCity('sid,theme');
        foreach ($goodsList as $k=>$v){
            foreach ($shops as $k1=>$v1){
                if($v['sid']==$v1['sid'] && ($v1['theme']=='Hotel' || $v1['theme']=='ktv')){
                    $goodsList[$k]['theme']='index';
                }
            }
        }
        $this->assign('goodsList',$goodsList);
        $this->newdisplay();
    }
    

    /*
     * 导航栏商品排序
     */
    public function getColumnOrder($array,$where=''){
        $enter=D('Enterprise','Service');
        $gwhere['jid']=$this->jid;
        $gwhere['gstatus']=1;
        $type='gid,gname,sid,goprice,gdprice,gstock,gimg,gsales,popularty,sale_num,ind_id,jid';
        if($where){
            $gwhere['gname']=$where['gname'];
            $gwhere['sid']=$where['sid'];
            $goodsArr = $enter->getGoodsSearch($gwhere,$type,'create_time desc');//查询该店铺下的全部商品
            $sql='SELECT gid,gname,sid,goprice,gdprice,gstock,gimg,gsales,sale_num,ind_id,jid,popularty,(gsales+sale_num) AS zhongshu FROM `azd_goods` WHERE ( `jid` = '.$this->jid.' ) AND ( `gstatus` = 1 ) AND ( `gname` LIKE \'%'.$where['gname'].'%\' ) ORDER BY zhongshu DESC';
        }else{
            $goodsArr = $enter->getGoodsSelect($gwhere,$type,'create_time desc');//查询该店铺下的全部商品
            $sql='SELECT gid,gname,sid,goprice,gdprice,gstock,gimg,gsales,sale_num,ind_id,jid,popularty,(gsales+sale_num) AS zhongshu FROM `azd_goods` WHERE ( `jid` = '.$this->jid.' ) AND ( `gstatus` = 1 ) ORDER BY zhongshu DESC';
        }
        if($array['type']=='xl_order'){//按照销量排序
            $goodsArr = $enter->query($sql);
        }else if($array['type']=='hp_order'){//按照好评排序
            foreach ($goodsArr as $k=>$v){
                $grade[$k]=$v['grade'];
            }
            array_multisort($grade,SORT_DESC,$goodsArr);//把数组通过grade值排序
        }else if($array['type']=='zh_order'){//智能排序
            $goods = $enter->query($sql);//按照销售数量进行排序
            $maxSales=(int)ceil($goods[0]['zhongshu']/50);//按照50为一个阶梯，计算共有多少阶梯
            for($i=$maxSales ; $i>0 ; $i--){//重大到小，以50为一个区间进行循环
                $maxscope=$i*50-1;//最大上限
                $minscope=($i-1)*50;//最小上限
                foreach ($goods as $v){//遍历数组，把每一区间的值取出，拼装成一个数组
                    if($v['zhongshu']>=$minscope && $v['zhongshu']<$maxscope){
                        $scopeArr[$i][]=$v;
                        if(count($scopeArr[$i])>1){//如果该数组中有2个及以上的元素，再去比较好评的高低
                            foreach ($scopeArr[$i] as $k1=>$v1){
                                $grade[$k1]=$v1['grade'];
                            }
                            array_multisort($grade,SORT_DESC,$scopeArr[$i]);//把数组通过grade值排序
                        }
                    }
                }
            }
            //把二维数组转为一维数组
            $goodsArr=array();
            foreach ($scopeArr as $v){
                foreach ($v as $v1){
                    $goodsArr[]=$v1;
                }
            }
        }else if($array['type']=='rq_order'){//人气排序
            foreach($goodsArr as $k=>$v){
                $popularty[$k]=$v['popularty'];
            }
            array_multisort($popularty,SORT_DESC,$goodsArr);
        }else{//没有排序
            if($where){
                $gwhere['gname']=$where['gname'];
                $goodsArr = $enter->getGoodsSearch($gwhere,$type,'create_time desc');//查询该店铺下的全部商品
            }else{
                $goodsArr = $enter->getGoodsSelect($gwhere,$type,'create_time desc');//查询该店铺下的全部商品
            }
        }
        if($array['id']){
            $id=(int)$array['id'];
            $where=array('status'=>1);
            $indarray=$enter->getIndustry($where,'ind_order desc',$id,'id,ind_name');//查询子分类
            //商品列表显示
            if(empty($indarray)){
                foreach ($goodsArr as $v){//如果为三级分类
                    if($v['ind_id']==$id){
                        $goodsList[]=$v;
                    }
                }
                $arr['indarray']='';
            }else{
                //如果为一，二级分类
                $indid=array($id);
                if($id==-1){
                    $indid=$indarray;
                }else{
                    foreach($indarray as $v){
                        $indid[]=$v['id'];
                        $indson=$enter->getIndustry($where,'ind_order desc',$v['id']);//查询子分类的id
                        foreach ($indson as $v1){
                            $indid[]=$v1['id'];
                        }
                    }
                }
                foreach ($goodsArr as $v){
                    foreach ($indid as $v1){
                        if($v['ind_id']==$v1['id'] || $v['ind_id']==$v1){
                            $goodsList[]=$v;
                        }
                    }
                }
                $arr['indarray']=$indarray;
            }
            $arr['goodsList']=$goodsList;
            return $arr;
        }else{
            $arr['goodsList']=$goodsArr;
            return $arr;
        }
    }

    /*
     * 显示前端排序类型，输出商品List
     * $modulecontent,是对应jid下的展示数组
     */
    public function getOrderList($modulecontent,$sids){
        $type='gid,gname,sid,goprice,gdprice,gstock,gimg,sale_num';
        foreach ($modulecontent as $v){
            if($v['en_module_sign']=='goods'){//判断是否有排行模块
                $result=D('Enterprise','Service')->ordergoods($v['en_module_content']['ordertype'],$v['en_module_content']['number'],$this->jid,$type,$sids);
                foreach ($result as $k1=>$v1){
                    $where['gid']=$v1['gid'];
                    $result[$k1]['grade']=D('Evaluate','Data')->sum($where,'grade')/D('Evaluate','Data')->count($where,'grade')/5*6;
                }
                return $result;
            }
        }
        return false;
    }

    //查询该企业版在该城市下的商铺数量
    //$jid,企业版ID
    //$cityid,城市id
    //$type,需要的内容
    //返回一个店铺的二维数组
    public function getShopsCity($type){
        $where['jid']=$this->jid;
        if((int)cookie('cityid') != 100000 && cookie('cityid')){
            $where['city']=(int)cookie('cityid');
        }
        $where['is_show']=1;
        $where['status']='1';
        $shops=D('Enterprise','Service')->getShopselect($where,$type);
        return $shops;
    }
    
    public function map(){
        //查询该企业版下的全部店铺
        $type='sid,sname,saddress,msaletel,mexplain,lng,lat,exterior';
        $shopList=D('Enterprise','Service')->getShopselect(array('jid'=>$this->jid,'status'=>1,'is_show'=>1),$type);
        $this->newdisplay();
    }
    //图片上传
    public function upload() {
        $uploadROOT = realpath(THINK_PATH.'../Public/');//上传地址的根目录
        $uploadSubPath = '/Upload/';//上传地址的子目录
        $subName = array('date','Y-m-d');
        $uploadPath =$uploadROOT.$uploadSubPath;
        if(!file_exists($uploadPath)) mkdirs($uploadPath,  0777);
        $uploadConfig = array(
            'rootPath'	=> $uploadPath,
            'subName'	=> $subName,
            'exts'		=> 'jpg,jpeg,png',
            'maxSize'	=> 556000
        );
        if( isset($_GET['conf']) && !empty($_GET['conf']) ) {
    
            $_UploadConfig = C( "UPLOAD_".strtoupper(trim($_GET['conf'])) );
    
            if( isset($_UploadConfig['rootPath']) && file_exists($_UploadConfig['rootPath']) ) {
                $uploadConfig['rootPath'] = $_UploadConfig['rootPath'];
            }
    
            if( isset($_UploadConfig['exts']) && !empty($_UploadConfig['exts']) ) {
                $uploadConfig['exts'] = $_UploadConfig['exts'];
            }
    
            if( isset($_UploadConfig['maxSize']) && !empty($_UploadConfig['maxSize']) ) {
                $uploadConfig['maxSize'] = $_UploadConfig['maxSize'];
            }
        }
        $attachment = new \Think\Upload( $uploadConfig );
        $url = array();
        $attachmentInfo1 = $attachment->uploadOne($_FILES['imgFile1']);
        //var_dump($attachmentInfo1);exit;
        $attachmentInfo2 = $attachment->uploadOne($_FILES['imgFile2']);
        $attachmentInfo3 = $attachment->uploadOne($_FILES['imgFile3']);
        if($attachmentInfo1 && is_array($attachmentInfo1)) {
            $url['idcardfront'] = '/Public'.$uploadSubPath.($subName?date('Y-m-d').'/':'').$attachmentInfo1['savename'];
        }
        if($attachmentInfo2 && is_array($attachmentInfo2)) {
            $url['idcardback'] = '/Public'.$uploadSubPath.($subName?date('Y-m-d').'/':'').$attachmentInfo2['savename'];
        }
        if($attachmentInfo3 && is_array($attachmentInfo3)) {
            $url['license'] = '/Public'.$uploadSubPath.($subName?date('Y-m-d').'/':'').$attachmentInfo3['savename'];
        }
        return $url;
    }
    
    
    
    
    
    
}