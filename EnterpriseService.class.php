<?php
namespace Common\Service;
use Think\Model;
/*
 * 企业版，模板控制
 */
class EnterpriseService extends Model{
    //查询address表一条记录
    public function getAddressFind($where,$type){
        $result=D('Address','Data')->find($where,$type);
        return $result;
    }
    //查询当前企业版下的商户
    public function getShopselect($where,$type){
        $result=D('Shop','Data')->select($where,$type);
        return $result;
    }
    //查询全部一级行业分类
    public function getIndustryOne($where,$type="*",$order='ind_order asc'){
        $result=D('Industry_class','Data')->select($where,$type,$order);
        return $result;
    }
    //查询Merchant表单个字段
    public function MerGetField($where,$type){
        $result=D('Merchant','Data')->getField($where,$type);
        return $result;
    }
    //查询公告消息表单个字段
    public function NoticeGetField($where,$order,$type,$limit){
        $result=D('Notice_message','Data')->getField($where,$order,$type,$limit);
        return $result;
    }
    //查询一条Merchant表记录
    public function MerFind($where,$type){
        $result=D('Merchant','Data')->find($where,$type);
        return $result;
    }
    //查询member表一条记录
    public function MemFind($where,$type) {
        $result=D('Member','Data')->find($where,$type);
        return $result;
    }
    
    //查询member表一个字段
    public function MemGetField($where,$type) {
        $result=D('Member','Data')->getField($where,$type);
        return $result;
    }
    
    //查询多条公告消息
    public function NoticeSelect($where,$order,$type){
        $result=D('Notice_message','Data')->select($where,$order,$type);
        return $result;
    }
    //查询一条公告消息
    public function NoticeFind($where,$type){
        $result=D('Notice_message','Data')->find($where,$type);
        preg_match_all("{<img(.*?)>}s",$result['content'],$matchs);
        $str=preg_replace("{<img(.*?)>}s",'',$result['content']);
        $result['img']=$matchs[0];
        $result['content']=$str;
        return $result;
    }
    
    /*查询展示广告图片
     * @where 是条件
     * @type 是查询字段
     * @order 是排序条件
     * @top 是展示位置 ，1为顶部，2为中部，3为底部，默认为0；如果为悬浮广告，top_right为中部靠右，top_left中部靠左，down_right底部靠右，down_left底部靠左
     * @page 是展示页面.例如字符串为推荐页面和推荐营销（例：index为首页）,整行为行业分类id；
     * @ad_type 是广告类型，1为单图，2为轮播，3为全屏，4为悬浮；
     * @return 返回一个数组或false
     */
    public function getAdmanage($where,$type,$order,$page='',$top=0,$ad_type=0){
        if($ad_type){
            $where['ad_type']=$ad_type;
        }else{
            $where['ad_type']=array(array('eq',1),array('eq',2),'or');
        }
        if((int)$page){
            $where['ad_industry']=array('like','%'.$page.'%');
        }else{
            $pwhere['ad_page']=array('like','%'.$page.'%');
            $pwhere['ad_marketing']=array('like','%'.$page.'%');
            $pwhere['_logic'] = 'or';
            $where['_complex'] = $pwhere;
        }
        if($top){
            $where['ad_position']=array('like','%'.$top.'%');
        }
        $result=M('admanage')
        ->field($type)
        ->where($where)
        ->order($order)
        ->find();
        if($result){
            $result['ad_img']=unserialize($result['ad_img']);//转译图片内容
            if($result['ad_position']){
                $result['ad_position']=explode(',',$result['ad_position']);
            }
            if($result['ad_img']['img1']){
                if(empty($result['ad_img']['img3']['url'])){
                    $result['ad_img']['img3']['url']='javascript:volid(0);';
                }else if(empty($result['ad_img']['img2']['url'])){
                    $result['ad_img']['img2']['url']='javascript:volid(0);';
                }else if(empty($result['ad_img']['img1']['url'])){
                    $result['ad_img']['img1']['url']='javascript:volid(0);';
                }
            }else{
                if(empty($result['ad_img']['url'])){
                    $result['ad_img']['url']='javascript:volid(0);';
                }
            }
//             if($ad_type == 4){
//                 $result['ad_position']=$result['ad_position'][0];
//             }
            return $result;
        }else{
            return false;
        }
    }
    
    //商家列表页调取全部商家,评价
    public function getShopList($where,$field,$code){
        //查询该店铺的评价及店铺信息
        $shopsgrade=M('shop')
        ->alias('AS s')
        ->field($field)
        ->join('LEFT JOIN azd_evaluate AS e ON s.sid=e.sid')
        ->where($where)
        ->group('s.sid')
        ->select();
        if($code){//营销活动列表
            foreach ($shopsgrade as $k=>$v){
                $shopsgrade[$k]['grade']=round($v['grade_num']/$v['id_num']*12/10,1);//插入商户评价
            }
        }else{//商家列表
            foreach ($shopsgrade as $k=>$v){
                $shopsgrade[$k]['img']=D('Shop','Service')->getgoods($v['sid'],'gid,gimg');//查询三张图片
                $shopsgrade[$k]['grade']=round($v['grade_num']/$v['id_num']*12/10,1);//插入商户评价
            }
        }
        return $shopsgrade;
    }
    
    /*营销活动页面商户显示
     * $where,where条件。
     * $order,排序条件
     * $code,是什么营销活动
     */
    public function getShopGrade($where,$field,$code,$point){
        //查询店铺信息及评价
        $shoplist=$this->getShopList($where,$field,$code);
        //计算和我的距离
        if($point){
            foreach ($shoplist as $k=>$v){
                $shoplist[$k]['distance']=$distance[$k]=D('Distance')->getDistance($point, $v['lat'], $v['lng']);
                if($distance[$k]/1000 > 1){
                    $shoplist[$k]['distance']=round($distance[$k]/1000,1).'公里';
                }else{
                    $shoplist[$k]['distance']=$distance[$k].'米';
                }
            }
            array_multisort($distance,SORT_ASC,$shoplist);
        }
        //设置活动标语
        $shoplist=$this->setMarketNotice($shoplist,$code);
//         if($code=='activity')$code='active';
//         if($code != 'upgrade' && $code != 'point'){
//             //查看店铺是否开启推荐设置活动
//             $Tj_list=D('Tuijian','Data')->select(array('jid'=>$where['s.jid'],'code'=>$code),'sid');

//             var_dump($Tj_list);die;
//             //循环去掉未设置推荐设置的店铺
//             $shopArr=array();
//             foreach ($shoplist as $v){
//                 foreach ($Tj_list as $v1){
//                     if($v['sid']==$v1['sid']){
//                         $shopArr[]=$v;
//                     }
//                 }
//             }
//         }else{
//             $shopArr = $shoplist;
//         }
        return $shoplist;
    }
    
    //设置商铺活动标语
    public function setMarketNotice($shoplist,$code){
        $noticeArr=array();
        $jid = $shoplist[0]['jid'];
        $mer = $this->MerFind(array('jid'=>$jid));
        if($code=='yyg'){//一元购活动
            foreach ($shoplist as $k=>$v){
                $where=array(
                    'oneshop_state'=>1,
                    'oneshop_starttime'=>array('elt',time()),
                    'oneshop_endtime'=>array('egt',time())
                );
                if($mer['oneshop_auth'] == 1){
                    $where['jid']=$jid;
                }else{
                    $where['sid']=$v['sid'];
                }
                $type=M('oneshop_goods')->where($where)->order('oneshop_createtime desc')->getField('oneshop_id');
                if($type){
                    $shoplist[$k]['notice']='<b>1元</b>抢购中';
                    $noticeArr[]=$shoplist[$k];
                }
            }    
        }else if($code=='vote'){//投票活动
            foreach ($shoplist as $k=>$v){
                $where=array(
                    'status'=>1,
                    'stime'=>array('elt',date('Y-m-d H:i:s',time())),
                    'etime'=>array('egt',date('Y-m-d H:i:s',time()))
                );
                if($mer['vote_auth'] == 1){
                    $where['jid']=$jid;
                }else{
                    $where['sid']=$v['sid'];
                }
                $type=M('vote')->where($where)->getField('item_type');
                if($type=='1'){
                    $shoplist[$k]['notice']='<b>会员</b>投票中';
                    $noticeArr[]=$shoplist[$k];
                }elseif($type=='2'){
                    $shoplist[$k]['notice']='<b>商品</b>投票中';
                    $noticeArr[]=$shoplist[$k];
                }elseif($type=='3'){
                    $shoplist[$k]['notice']='<b>自定义</b>投票中';
                    $noticeArr[]=$shoplist[$k];
                }
            }
        }else if($code=='invest'){//消费投资活动
            foreach ($shoplist as $k=>$v){
                $where=array(
                    'status'=>1
                );
                if($mer['invest_auth'] == 1){
                    $where['jid']=$jid;
                }else{
                    $where['sid']=$v['sid'];
                }
                $type=M('touzi')->where($where)->getField('tz_type');
                if($type==1){
                    $shoplist[$k]['notice']='<b>店铺</b>投资返利中';
                    $noticeArr[]=$shoplist[$k];
                }else if($type==2){
                    $shoplist[$k]['notice']='<b>商品</b>投资返利中';
                    $noticeArr[]=$shoplist[$k];
                }
            }
        }else if($code=='voucher'){//优惠券活动
            foreach ($shoplist as $k=>$v){
                $where=array(
                    'vu_status'=>1,
                    'vu_stime'=>array('elt',date('Y-m-d H:i:s',time())),
                    'vu_etime'=>array('egt',date('Y-m-d H:i:s',time()))
                );
                if($mer['jq_auth'] == 1){
                    $where['vu_jid']=$jid;
                }else{
                    $where['vu_sid']=$v['sid'];
                }
                $type=M('voucher')->where($where)->order('vu_price desc')->getField('vu_price');
                if($type){
                    $shoplist[$k]['notice']='最高减<b>'.$type.'元</b>';
                    $noticeArr[]=$shoplist[$k];
                }
            }
        }else if($code=='limit'){//限时抢购活动(未完成)
            foreach ($shoplist as $k=>$v){
                $where=array(
                    'l_status'=>1,
                    'l_stime'=>array('elt',time()),
                    'l_etime'=>array('egt',time())
                );
                if($mer['limit_auth'] == 1){
                    $where['l_jid']=$jid;
                }else{
                    $where['l_sid']=$v['sid'];
                }
                $type=M('limit_buy')->where($where)->order('l_etime asc')->getField('l_etime');
                if($type){
                    $shoplist[$k]['notice']='距离结束还有<b>'.date('j H:i:s',($type-time())).'</b>';
                    $noticeArr[]=$shoplist[$k];
                }
            }
        }else if($code=='upgrade'){//升级消费商活动(未完成)
            $where=array(
                'jid'=>$jid,
                'status'=>1,
            );
            $type=M('upgrade')->where($where)->find();
            if($type){
                foreach ($shoplist as $k=>$v){
                    $shoplist[$k]['notice']='消费商<b>返利</b>进行中';
                    $noticeArr[]=$shoplist[$k];
                }
            }
        }else if($code=='redpacket'){//抢红包活动
            foreach ($shoplist as $k=>$v){
                $where=array(
                    'redpacket_status'=>1,
                    'redpacket_starttime'=>array('elt',time()),
                    'redpacket_endtime'=>array('egt',time()),
                    'redpacket_delet'=>0
                );
                if($mer['redpacket_auth'] == 1){
                    $where['jid']=$jid;
                }else{
                    $where['sid']=$v['sid'];
                }
                $type=M('redpacket')->where($where)->order('redpacket_lowmoney desc')->find();
                if($type['redpacket_ctype']==1){
                    if($type['redpacket_lowmoney'] >= $type['redpacket_uppmoney']){
                        $shoplist[$k]['notice']='<b>'.$type['redpacket_lowmoney'].'元</b>优惠红包';
                    }else{
                        $shoplist[$k]['notice']='<b>'.$type['redpacket_uppmoney'].'元</b>优惠红包';
                    }
                    $noticeArr[]=$shoplist[$k];
                }else if($type['redpacket_ctype']==2){
                    if($type['redpacket_lowmoney'] >= $type['redpacket_uppmoney']){
                        $shoplist[$k]['notice']='<b>'.$type['redpacket_lowmoney'].'元</b>优惠红包';
                    }else{
                        $shoplist[$k]['notice']='<b>'.$type['redpacket_uppmoney'].'元</b>优惠红包';
                    }
                    $noticeArr[]=$shoplist[$k];
                }
            }
        }else if($code=='dzp'){//大转盘活动
            foreach ($shoplist as $k=>$v){
                $where=array(
                    'status'=>1,
                    'stime'=>array('elt',date('Y-m-d H:i:s',time())),
                    'etime'=>array('egt',date('Y-m-d H:i:s',time())),
                );
                if($mer['dzp_auth'] == 1){
                    $where['z_jid']=$jid;
                }else{
                    $where['z_sid']=$v['sid'];
                }
                $type=M('dazhuanpan')->where($where)->getField('set');
                if($type){
                    $set=unserialize($type);
                    //拼装数组，生成奖品数组
                    $dzpArr=array();
                    foreach ($set['ptype'] as $k1=>$v1){
                        if($v1==1){//实物
                            $dzpArr[]=array($v1,$set['pname'][$k1]);
                        }else if($v1==2){//优惠卷
                            $dzpArr[]=array($v1,$set['pvid'][$k1]);
                        }else if($v1==3){//现金红包
                            $dzpArr[]=array($v1,$set['xianjinvid'][$k1]);
                        }else if($v1==4){//消费红包
                            $dzpArr[]=array($v1,$set['xiaofeivid'][$k1]);
                        }
                    }
                    shuffle($dzpArr);//随机数组
                    if($dzpArr[0][0]==1){//实物
                        $shoplist[$k]['notice']='有机会赢取<b>'.$dzpArr[0][1].'</b>';
                        $noticeArr[]=$shoplist[$k];
                    }elseif($dzpArr[0][0]==2){//优惠卷
                        $where=array(
                            'vu_id'=>$dzpArr[0][1],
                            'vu_status'=>1,
                            'vu_stime'=>array('elt',date('Y-m-d H:i:s',time())),
                            'vu_etime'=>array('egt',date('Y-m-d H:i:s',time()))
                        );
                        $num=M('voucher')->where($where)->order('vu_price desc')->getField('vu_price');
                        if($num){
                            $shoplist[$k]['notice']='<b>'.$num.'元</b>优惠红包';
                            $noticeArr[]=$shoplist[$k];
                        }
                    }elseif($dzpArr[0][0]==3){//现金红包
                        $where=array('id'=>$dzpArr[0][1]);
                        $num=M('dzp_redpacket')->where($where)->getField('redpacket_money');
                        $shoplist[$k]['notice']='有机会赢取<b>现金红包'.$num.'元</b>';
                        $noticeArr[]=$shoplist[$k];
                    }elseif($dzpArr[0][0]==4){//消费红包
                        $where=array('id'=>$dzpArr[0][1]);
                        $num=M('dzp_redpacket')->where($where)->getField('redpacket_money');
                        $shoplist[$k]['notice']='有机会赢取<b>消费红包'.$num.'元</b>';;
                        $noticeArr[]=$shoplist[$k];
                    }
                }
            }
        }else if($code=='active'){//优惠活动
            foreach ($shoplist as $k=>$v){
                $where=array(
                    'yh_state'=>1,
                    'yh_starttime'=>array('elt',date('Y-m-d H:i:s',time())),
                    'yh_endtime'=>array('egt',date('Y-m-d H:i:s',time())),
                );
                if($mer['active_auth'] == 1){
                    $where['jid']=$jid;
                }else{
                    $where['sid']=$v['sid'];
                }
                $type=M('specialoffers')->field('yh_type,yh_content')->where($where)->order('yh_createtime desc')->find();
                if($type['yh_type']==1){
                    $maxArr = explode(',',$type['yh_content']);
                    $priseArr  = array();
                    $arr1 = array();
                    foreach ($maxArr as $k1=>$v1){
                        $priseArr[] = $arr = explode('_',$v1);
                        $arr1[] = $arr[0];
                    }
                    array_multisort($arr1,SORT_ASC,$priseArr);
                    $shoplist[$k]['notice']='商品<b>满'.$priseArr[0][0].'减'.$priseArr[0][1].'</b>中';
                    $noticeArr[]=$shoplist[$k];
                }else if($type['yh_type']==2){
                    $shoplist[$k]['notice']='商品<b>折扣'.$type['yh_content'].'折</b>中';
                    $noticeArr[]=$shoplist[$k];
                }
            }
        }else if($code=='point'){//积分活动
            //查询总店设置的积分活动的店铺
            $mersid=M('point_setting')->field('jid,sids')->where(array('jid'=>$shoplist[0]['jid']))->find();
            if($mersid['sids']){
                $sidArr=explode(',',$mersid['sids']);
                foreach ($shoplist as $k=>$v){
                    foreach ($sidArr as $v1){
                        $pointGoods=M('point_goods')->where(array('sid'=>$v1,'status'=>1))->find();
                        if($pointGoods && $v['sid']==$v1){
                            $shoplist[$k]['notice']='<b>积分</b>兑换中';
                            $noticeArr[]=$shoplist[$k];
                        }
                    }
                }
            }else{
                foreach ($shoplist as $k=>$v){
                    $pointGoods=M('point_goods')->where(array('sid'=>$v['sid'],'status'=>1))->find();
                    if($pointGoods){
                        $shoplist[$k]['notice']='<b>积分</b>兑换中';
                        $noticeArr[]=$shoplist[$k];
                    }
                }
            }
        }
        return $noticeArr;
    }
    //营销活动页面查询
    public function getTuijianFind($where,$type){
        $result=D('Tuijian','Data')->find($where,$type);
        return $result;
    }
    
    //显示商品品牌列表
    public function getBlandSelect($where,$limit='',$order){
        $result=D('Shop_bland','Data')->select($where,$limit='',$order);
        return $result;
    }
    
    //获取品牌单个字段
    public function getBlandField($where,$type){
        $result=D('Shop_bland','Data')->getField($where,$type);
        return $result;
    }
    
    //获取品牌下的商品
    public function getGoodsSelect($where,$type,$order,$page){
        $result=D('Goods','Data')->select($where,$type,$order,$page);
        //查询评价
        $gradeid='';
        foreach($result as $v){
            $gradeid.=$v['gid'].',';
        }
        $gidstr=trim($gradeid,',');
        $gwhere['gid']=array('in',$gidstr);
        $grade=D('Evaluate','Data')->select($gwhere,'gid,grade');//获取评价
        $num='';
        foreach ($grade as $v){//重新拼装数组
            $gradearr[$v['gid']][]=$v['grade'];
        }
        foreach ($gradearr as $k=>$v){//统计每个商品的评价百分比
            $num=array_sum($v);
            $arr[$k]=round($num/count($v),1)*20;
        }
        foreach ($arr as $k=>$v){//重新拼装数组
            foreach($result as $k1=>$v1){
                if($v1['gid']==$k){
                     $result[$k1]['grade']=$v;
                }
            }
        }
        return $result;
    }
    
    /*查询行业分类，并显示其下的子分类
     * @$id 行业id号
     * @$where 查询的where条件
     * @$order 排序方式
     * 返回一个数组
     */
    public function getIndustry($where,$order,$id,$type){
        if($id){//有ID，就去查询该id下级的分类
            $lwhere=array('id'=>$id);
            $level=D('Industry_class','Data')->find($lwhere);
            if($level['cateid']){
                $where['cateid']=array('like','%-'.$level['id']);
            }else{
                $where['cateid']=$level['id'];
            }
            $result=D('Industry_class','Data')->select($where,$type,$order);
        }else{//没有ID，就显示所有二级分类
            $result=D('Industry_class','Data')->select($where,$type,$order);
            foreach($result as $k=>$v){
                if($v['cateid']==0){
                    unset($result[$k]);
                }else{
                    $arr=explode('-',$v['cateid']);
                    if(count($arr)>=2){
                        unset($result[$k]);
                    }
                }
            }
        }
        return $result;
    }
    
    /*
     * 商品列表页中的行业分类（输入一级分类ID，拼装返回一个该ID下的全部二级，三级分类。如果没有ID，则返回全部一级分类）
     */
    public function getGoodsIndustry($where,$type='*',$id,$jid,$sid=''){
        $model=M('goods');
        if($id){//如果有一级分类id
            $wheretwo['cateid']=(string)$id;
            $indtwo=D('Industry_class','Data')->select($wheretwo,$type);//查询该分类下的所以二级分类
            foreach ($indtwo as $k=>$v){
                $wherethree['cateid']=array('like','%'.$v['id']);
                $next=D('Industry_class','Data')->select($wherethree,$type);
                foreach ($next as $k1=>$v1){
                    if($sid){
                        $str='SELECT gimg,(gsales+sale_num) AS zhongshu FROM `azd_goods` WHERE sid IN ('.$sid.') AND jid='.$jid.' AND ind_id='.$v1['id'].' ORDER BY zhongshu DESC LIMIT 1';
                    }else{
                        $str='SELECT gimg,(gsales+sale_num) AS zhongshu FROM `azd_goods` WHERE jid='.$jid.' AND ind_id='.$v1['id'].' ORDER BY zhongshu DESC LIMIT 1';
                    }
                    $arr=$model->query($str);
                    if($arr[0]['gimg']){
                        $next[$k1]['img']=$arr[0]['gimg'];
                    }
                }
                $indtwo[$k]['next']=$next;
            }
            $result=$indtwo;
        }else{
            $result=D('Industry_class','Data')->select($where,$type);
        }
        return $result;
    }

    //获取商家下的行业分类信息
    public function getAllInd($jid){
        $ind_id = D("Shop",'Data')->select(array('jid'=>$jid),'ind_id');
        $ind_ids = array_column($ind_id,'ind_id');
        $ind_ids = array_unique($ind_ids);
        $all_ind_id = array();
        foreach ($ind_ids as $v){
            if($v==0){
                continue;
            }
            $indtwo=D('Industry_class','Data')->select(array('cateid'=>$v),"*");//查询该分类下的所有二级分类
            foreach ($indtwo as $v1){
                $next=D('Industry_class','Data')->select(array('cateid'=>array('like','%'.$v1['id'])),"*");
                $all_ind_id = array_merge($all_ind_id,$next);
            }
            $all_ind_id = array_merge($all_ind_id,$indtwo);
        }
        return $all_ind_id;
    }

    //查询一条行业分类信息
    public function getIndFind($where,$type){
        $result=D('Industry_class','Data')->find($where,$type);
        return $result;
    }
    
    //调取店铺的模板功能
    public function getModulecontent($jid,$order,$type=''){
        $where=array('jid'=>$jid);
        $where['status']='1';
        if($type){
            $where['en_module_sign']=$type;
        }
        $active = D('EnModuleContent','Data')->select($where,'',$order);//取出所有模块，进行排序
        foreach ($active as $k=>$v){
           $active[$k]['en_module_content'] = unserialize($v['en_module_content']);
        }
        if(!empty($active)){
          return $active;
		}else{
          return false;
		}
	 }
    
    //调去shop表单个字段
    public function getShopGetField($where,$type){
        $result=D('Shop','Data')->getField($where,$type);
        return $result;
    }
    
    //查询首页显示的行业分类
    public function getIndustryCol($where,$type,$order){
        // 查询多条记录
        $result=D('Industry_col','Data')->select($where,$type="*",'`order`');
        return $result;
    }
    
    //查询热门搜索
    public function getSearchHot($where,$type,$limit,$order='searchnum desc'){
        if($type=='goods'){
            $where['searchtype']='goods';
        }else if($type=='shops'){
            $where['searchtype']='shops';
        }
        $result=D('Searchhot','Data')->select($where,'*',$order,$limit);
        return $result;
    }
    
    //增加一条搜索记录
    public function setSearchHotAdd($data){
        if($data['id'] && $data['searchkey']){//判断是否在搜索表内
            $where=array('id'=>$data['id']);
            $result=D('Searchhot','Data')->setInc($where);
        }else if($data['searchkey']){
            $where=array('jid'=>$data['jid'],'searchkey'=>$data['searchkey'],'searchtype'=>$data['searchtype']);//判断是否在搜索表内
            $re=D('Searchhot','Data')->find($where);
            if($re){
                $result=D('Searchhot','Data')->setInc($where);//自增1
            }else{
                unset($data['keyword']);
                $result=D('Searchhot','Data')->add($data);//增加一条记录
            }
        }else{
            $result=false;
        }
        $cid=$data['id']?$data['id']:($re['id']?$re['id']:$result);//判断储存cookie的ID
        $cookieArr=json_decode(cookie('mysearch'),true);
        $num=count($cookieArr);//计算历史搜索数量
        if($cookieArr){//判断是否有历史搜索
            foreach($cookieArr as $k=>$v){
                if($v[0]==$cid){//如果搜索内容在历史搜索中，更新搜索时间
                    $v[1]=time();
                }else {
                    if($num>=8){//如果搜索内容不在历史搜索中，且已经大于8个
                        foreach ($cookieArr as $k1=>$v1){
                            $id[$k1] = $v1[0];
                            $time[$k1] = $v1[1];
                        }
                        array_multisort($time,SORT_NUMERIC,SORT_DESC,$id,SORT_STRING,SORT_ASC,$array);//通过时间排序
                        unset($array[$num-1]);//去掉时间最小的
                        $array[$num-1]=array($cid,time());//增加当前搜索内容
                        cookie('mysearch',json_encode($array),24*60*60);
                    }else{////如果搜索内容不在历史搜索中，且小于8个
                        $cookieArr[$num]=array($cid,time());
                        cookie('mysearch',json_encode($cookieArr),24*60*60);
                    }
                }
            }
        }else{//没有历史搜索的情况
            $mysearch=json_encode(array(0=>array($cid,time())));
            cookie('mysearch',$mysearch,24*60*60);
        }
        return $result;
    }
    
    //搜索页面的商品展示
    public function getGoodsSearch($where,$type,$order,$page){
        $gwhere=array('jid'=>$where['jid']);
        if($where['ind_id']){
            $gwhere['ind_id']=$where['ind_id'];
        }
        $gwhere['sid']=$where['sid'];
        $gwhere['gname']=array('like','%'.$where['gname'].'%');
        $result=D('Goods','Data')->select($gwhere,$type,$order,$page);//查询模糊查询结果
        foreach ($result as $k=>$v){
            $nwhere['sp_gid']=array('eq',$v['gid']);
            $nwhere['addtime']=array(array('LT',time()),array('GT',(time()-30*24*60*60)),'and');
            $result[$k]['num']=D('Goods_snapshot','Data')->count($nwhere)+$result[$k]['sale_num'];
            $result[$k]['grade']=(D('Evaluate','Data')->sum(array('gid'=>$v['gid']),'grade')/D('Evaluate','Data')->count(array('gid'=>$v['gid'])))*20;  
        }
        return $result;
    }
    
    //查询并显示全部城市列表
    //$type查询需要显示的内容
    public function getCityList($type){
        $province=D('Address','Data')->select('apid=0','*');//查询全部的省份
        $cityArr=array();
        $str='';
        foreach ($province as $v){//遍历查询全部城市
            if($v['aid']!='110000' && $v['aid']!='310000' && $v['aid']!='120000' && $v['aid']!='500000' && $v['aid']!='990000'){
                $str.=$v['aid'].',';
            }else{
                $cityArr[]=$v;
                unset($cityArr[4]);
            }
        }
        $idstr=trim($str,',');
        $where['apid']=array('in',$idstr);
        $citylist=D('Address','Data')->select($where,'*');//查询全部的城市
        $cityArr=array_merge($cityArr,$citylist);
        return $cityArr;
    }
    
    //显示一级分类，并显示该分类下的店铺数量
    public function getIndustryShopsNum($where){
        $indone=M('industry_class')
        ->field('id,ind_name,COUNT(s.ind_id) AS ind_num')
        ->alias('AS i')
        ->join('RIGHT JOIN azd_shop AS s ON i.id=s.ind_id')
        ->where($where)
        ->group('s.ind_id')
        ->order('ind_order ASC')
        ->select();
        return $indone;
    }
    
 //系统商品排序
	public function ordergoods($ordertype,$number,$jid,$type='*',$sids){
        if(empty($ordertype)){
           return false;
		}
		if($ordertype == 'sales' && $number!=0){
           //销量排序
		   $goods = M('goods')->field($type)->where(array('sid'=>array('in',$sids),'gstatus'=>1))->limit($number)->select();
		   foreach($goods as $k => $v){
              $where2['sp_gid'] = array('eq',$v['gid']);
			  $where2['addtime'] = array(array('LT',time()),array('GT',(time()-30*24*60*60)),'and');
			  $goods[$k]['num'] = D('Goods_snapshot','Data')->count($where2)+$v['sale_num'];
			  $num[$k] = $goods[$k]['num'];
		   }
		   array_multisort($num,SORT_DESC,$goods);
		}
		if($ordertype == 'addtime' && $number!=0){
           //最新上架
		   $goods = M('goods')->field($type)->where(array('sid'=>array('in',$sids),'gstatus'=>1))->order('create_time desc')->limit($number)->select();
		   foreach($goods as $k => $v){
              $where2['sp_gid'] = array('eq',$v['gid']);
			  $where2['addtime'] = array(array('LT',time()),array('GT',(time()-30*24*60*60)),'and');
			  $goods[$k]['num'] = D('Goods_snapshot','Data')->count($where2)+$v['sale_num'];
		   }
		}
		if($ordertype == 'reputable' && $number!=0){
           //好评最多
		   $goodid = M('evaluate')->where(array('jid'=>$jid,'gid'=>array('neq',0)))->group('gid')->limit($number)->field('gid')->select();
		   foreach($goodid as $k => $v){
              $sum = M('evaluate')->where(array('gid'=>$v['gid']))->sum('grade');
			  $count = M('evaluate')->where(array('gid'=>$v['gid']))->count();
			  $goodid[$k]['grade'] = $sum/$count;
			  $grade[$k] = $goodid[$k]['grade'];
		   }
		   array_multisort($grade,SORT_DESC,$goodid);
		   $goods = array();
		   foreach($goodid as $kk => $vv){
              $goods[$kk] = M('goods')->field($type)->where(array('gid'=>$vv['gid'],'gstatus'=>1))->find();
			  $where2['sp_gid'] = array('eq',$v['gid']);
			  $where2['addtime'] = array(array('LT',time()),array('GT',(time()-30*24*60*60)),'and');
			  $goods[$kk]['num'] = D('Goods_snapshot','Data')->count($where2)+$goods[$kk]['sale_num'];
		   }
		   //好评数量不足的情况按照销量来排
           if(count($goodid)<$number){
              $newnumber = $number-count($goodid);
			  $goodid = array_column($goodid,'gid');
			  $goods2 = M('goods')->field($type)->where(array('sid'=>array('in',$sids),'gid'=>array('not in',$goodid),'gstatus'=>1))->limit($number)->select();
		      foreach($goods2 as $kkk => $vvv){
				  $where3['sp_gid'] = array('eq',$vvv['gid']);
				  $where3['addtime'] = array(array('LT',time()),array('GT',(time()-30*24*60*60)),'and');
				  $goods2[$kkk]['num'] = D('Goods_snapshot','Data')->count($where3)+$vvv['sale_num'];
				  $num[$kkk] = $goods2[$kkk]['num'];
		      }
		      array_multisort($num,SORT_DESC,$goods2);
		   }
		   $goods = array_merge($goods,$goods2);
		}
		//判断商品是否是优惠活动
		   $auth = M('merchant')->field('active_auth,invest_auth,limit_auth')->where(array('jid'=>$jid))->find();
		   if($auth['active_auth'] == 1){
		       $active = M('specialoffers')->where(array('jid'=>$jid,'sid'=>0,'yh_state'=>array('eq',1)))->select();
		   }else{
		       $active = M('specialoffers')->where(array('sid'=>array('in',$sids),'yh_state'=>array('eq',1)))->select();
		   }
		   //判断商品是否是消费投资
		   if($auth['invest_auth']==1){
		       $invest = M('touzi')->where(array('jid'=>$jid,'sid'=>0,'status'=>array('eq',1)))->select();
		   }else{
		       $invest = M('touzi')->where(array('sid'=>array('in',$sids),'status'=>array('eq',1)))->select();
		   }
		   //判断商品是否是限时限量购
		   if($auth['limit_auth']==1){
		       $limit = M('limit_goods')->where(array('jid'=>$jid,'sid'=>0,'status'=>array('eq',1),'lstock'=>array(array('eq',-1),array('gt',0),'or')))->select();
		   }else{
		       $limit = M('limit_goods')->where(array('sid'=>array('in',$sids),'status'=>array('eq',1),'lstock'=>array(array('eq',-1),array('gt',0),'or')))->select();
		   }
		   foreach ($goods as $k=>$v){
		       foreach ($active as $v1){//优惠活动
		           if($v['gid']==$v1['gid']){
		               $goods[$k]['active']='active';
		           }
		       }
		       foreach ($invest as $v1){//消费投资
		           if($v1['sid']){//有sid的情况
		               if($v1['gid']){//有gid的情况
		                   $gidArr = explode(',',$v1['gid']);
		                   foreach ($gidArr as $v2){
		                       if($v2==$v['gid']){
		                           $goods[$k]['invest']='invest';
		                       }
		                   }
		               }else{
		                   if($v['sid']==$v1['sid']){
		                       $goods[$k]['invest']='invest';
		                   }
		               }
		           }else{
		               if($v1['gid']){//有gid的情况
		                   $gidArr = explode(',',$v1['gid']);
		                   foreach ($gidArr as $v2){
		                       if($v2==$v['gid']){
		                           $goods[$k]['invest']='invest';
		                       }
		                   }
		               }else{
		                   $goods[$k]['invest']='invest';
		               }
		           }
		       }
		       foreach ($limit as $v1){//限时限量购
		           if($v1['gid']==$v['gid']){
		               $goods[$k]['limit']='limit';
		           }
		       }
		   }
		return $goods;
	}
    
    /*
     * 导航栏店铺
     * $cityid 城市id编号
     * $point 客户端定位坐标
     * $array 提交数组，id为行业分类数组，type为排序，busniess为商圈
     * $keyword 搜索关键字
     * $code 营销活动 array('code' 对应的营销活动
     *                   'sid' 相应设置的营销多动的店铺id)
     * return 返回一个相应要求的店铺数组
     */
    public function getColumnShop($jid,$cityid,$point,$array,$keyword='',$code=''){
        if($keyword){//有搜索的情况
            $where=array('s.jid'=>$jid,'is_show'=>'1','s.status'=>'1','sname'=>array('like','%'.$keyword.'%'));
            if($cityid != 100000){
                $where['city']=$cityid;
            }
        }else{
            $where=array('s.jid'=>$jid,'is_show'=>'1','s.status'=>'1');
            if($cityid != 100000){
                $where['city']=$cityid;
            }
        }
        if($code['code']){//在营销管理活动的情况
            $where=array('s.jid'=>$jid,'is_show'=>'1','s.status'=>'1','s.sid'=>array('in',$code['sid']));
            if($cityid != 100000){
                $where['city']=$cityid;
            }
            $field='s.jid,s.sid,sname,lng,lat,logo,theme,ind_id,views_count,SUM(grade) AS grade_num,COUNT(e.id) AS id_num';
            $shoplist=$this->getShopGrade($where,$field,$code['code'],$point);
        }else{
            $field='s.jid,s.sid,sname,lng,lat,logo,ind_id,theme,views_count,SUM(grade) AS grade_num,COUNT(e.id) AS id_num';
            $shoplist = $this->getShopList($where,$field);//查询商户的信息及评价
            //计算店铺距离我的距离并加入数组
            if($point){
                foreach ($shoplist as $k=>$v){
                    $shoplist[$k]['distance']=$distance[$k]=D('Distance')->getDistance($point, $v['lat'], $v['lng']);
                }
            }
        }
        //查询店铺的销量
        $where['gstatus']=1;
        $shopArr=M('shop')->field('s.sid,(COUNT(gsales)+COUNT(sale_num)) AS gnum')->alias('AS s')->join('LEFT JOIN azd_goods AS g ON s.sid=g.sid')
        ->where($where)->group('s.sid')->order('gnum DESC')->select();
        foreach ($shoplist as $k=>$v){//循环遍历插入店铺销量
            foreach ($shopArr as $k1=>$v1){
                if($v['sid']==$v1['sid']){
                    $shoplist[$k]['gnum']=$gnum[$k]=$v1['gnum'];
                }
            }
        }
        //排序
        if($array['type']=='xl_order'){//销量排序
            array_multisort($gnum,SORT_DESC,$shoplist);
        }else if($array['type']=='hp_order'){//好评排序
            foreach ($shoplist as $k=>$v){
                $grade[$k]=$v['grade'];
            }
            array_multisort($grade,SORT_DESC,$shoplist);
        }else if($array['type']=='rq_order'){//人气排序(未完成)
            foreach ($shoplist as $k=>$v){
                $popularty[$k]=$v['views_count'];
            }
            array_multisort($popularty,SORT_DESC,$shoplist);
        }else if($array['type']=='jl_order'){//距离排序
            if($point){array_multisort($distance,SORT_ASC,$shoplist);}else{return 'error';}
        }else if($array['type']=='zh_order'){//综合排序
            if($point){//可以定位的情况下
                array_multisort($distance,SORT_ASC,$shoplist);//确定最远距离
                $distancelast=end($shoplist);//取最后一个元素
                $disNum=$distancelast['distance']>50000?50000:$distancelast['distance'];//限制最大距离为50km
                $distanceNum=(int)ceil($disNum/500);//把距离按500米一个阶梯分配
                for($i=0;$i<=$distanceNum;$i++){//以500米为一个区间，循环分组
                    $distanceMin=$i*500;//下限
                    $distanceMax=($i+1)*500-1;//上限
                    foreach($shoplist as $v){//按照500米一个区间分割数组
                        if($v['distance']>=$distanceMin && $v['distance']<=$distanceMax){
                            $distanceArr[$i][]=$v;
                        }
                    }
                    if(count($distanceArr[$i])>1){//如果一个区间内有两个及以上元素，再按照销量排序
                        foreach ($shoplist as $k=>$v){//确定最大销量
                            $gorder[$k]=$v['gnum'];
                        }
                        array_multisort($gorder,SORT_DESC,$shoplist);
                        $gmax=(int)ceil($shoplist[0]['gnum']/50);//把销量按50米一个阶梯分配
                        for($j=$gmax;$j>0;$j--){
                            $gnumMin=($j-1)*50;//下限
                            $gnumMax=$j*50-1;//上限
                            foreach ($distanceArr[$i] as $v){//按照50个一个区间分类数组
                                if($v['gnum']>=$gnumMin && $v['gnum']<=$gnumMax){
                                    $distanceArr[$i][$j][]=$v;
                                }
                            }
                            if(count($distanceArr[$i][$j])>1){
                                foreach ($distanceArr[$i][$j] as $k=>$v){
                                    $grade[$k]=$v['grade'];
                                }
                                array_multisort($grade,SORT_DESC,$distanceArr[$i][$j]);
                            }
                        }
                    }
                }
                $shoplist=D('Distance')->array_function($distanceArr);//拼装为一维数组
            }else{//不能定位的情况下
                array_multisort($gnum,SORT_DESC,$shoplist);
                $gmax=(int)ceil($shoplist[0]['gnum']/50);//把销量按50米一个阶梯分配
                for($j=$gmax;$j>0;$j--){
                    $gnumMin=($j-1)*50;//下限
                    $gnumMax=$j*50-1;//上限
                    foreach ($shoplist as $v){//按照50个一个区间分类数组
                        if($v['gnum']>=$gnumMin && $v['gnum']<=$gnumMax){
                            $distanceArr[$j][]=$v;
                        }
                    }
                    if(count($distanceArr[$j])>1){
                        foreach ($distanceArr[$j] as $k=>$v){
                            $grade[$k]=$v['grade'];
                        }
                        array_multisort($grade,SORT_DESC,$distanceArr[$j]);
                    }
                }
                $shoplist=D('Distance')->array_function($distanceArr);//拼装为一维数组
            }
        }
        //行业分类
        if($array['id']){
            $id=(int)$array['id'];
            $indArr=array();
            foreach ($shoplist as $k=>$v){
                if($v['ind_id'] == $id){
                    $indArr[]=$v;
                }
            }
            $shoplist=$indArr;
        }
        //商圈分类
        if($array['business']){
            $business=(int)$array['business'];
            if($point){
                $fujArr=array();
                if($business==5){//附近500m范围的店铺
                    foreach ($shoplist as $k=>$v){
                        if($v['distance']<=500){
                            $fujArr[]=$v;
                        }
                    }
                }else if($business==10){//附近1000m范围的店铺
                    foreach ($shoplist as $k=>$v){
                        if($v['distance']<=1000){
                            $fujArr[]=$v;
                        }
                    }
                }else if($business==30){//附近3000m范围的店铺
                    foreach ($shoplist as $k=>$v){
                        if($v['distance']<=3000){
                            $fujArr[]=$v;
                        }
                    }
                }else if($business==50){//附近5000m范围的店铺
                    foreach ($shoplist as $k=>$v){
                        if($v['distance']<=5000){
                            $fujArr[]=$v;
                        }
                    }
                }else if($business==100){//附近10000m范围的店铺
                    foreach ($shoplist as $k=>$v){
                        if($v['distance']<=10000){
                            $fujArr[]=$v;
                        }
                    }
                }else{
                    //查询店铺商圈
                    if($keyword){
                        $bwhere=array('s.jid'=>$jid,'s.city'=>$cityid,'is_show'=>'1','l.circle'=>$business,'sname'=>array('like','%'.$keyword.'%'));
                    }else{
                        $bwhere=array('s.jid'=>$jid,'s.city'=>$cityid,'is_show'=>'1','l.circle'=>$business);
                    }
                    $businessArr = M('location')
                    ->field('s.sid,l.circle')->alias('AS l')->join('JOIN azd_shop AS s ON s.sid=l.sid')
                    ->where($bwhere)->select();
                    if(!$businessArr){//如果没有查询到代表没有店铺
                        unset($shoplist);
                    }else{
                        foreach ($shoplist as $k=>$v){//如果有商圈的话就筛选数组
                            foreach ($businessArr as $v1){
                                if($v['sid']==$v1['sid']){
                                    $fujArr[]=$v;
                                }
                            }
                        }
                    }
                }
                $shoplist=$fujArr;
            }else{
                return 'error';
            }
        }
        if($point){//计算距离
            foreach ($shoplist as $k=>$v){
                if($v['distance']/1000 > 1){
                    $shoplist[$k]['distance']=round($v['distance']/1000,1).'公里';
                }else{
                    $shoplist[$k]['distance']=$v['distance'].'米';
                }
            }
        }
        return $shoplist;
    }
    
}