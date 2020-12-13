<?php
namespace app\admin\controller;
use \think\Db;

class Index extends Main
{
    public function index()
    {
        return $this->fetch();
    }

    public function child()
    {
        return $this->fetch();
    }

    public function main()
    {
        return $this->fetch();
    }

    public function welcome()
    {
        $user = session('user');
        $where = [];
        if($user['county']){
            $where['county'] = ['eq', $user['county']];
        }
        //待审技工
        $mechanic = Db::name('mechanic')->where(['checked'=>0])->where($where)->count();
        //待审工长
        $gongzhang = Db::name('gongzhang')->where(['checked'=>0])->where($where)->count();
        //待审设计师
        $designer = Db::name('designer')->where(['checked'=>0])->where($where)->count();
        //待审装饰公司
        $company = Db::name('company')->where(['checked'=>0])->where($where)->count();
        //待审商家
        $shop = Db::name('shop')->where(['checked'=>0])->where($where)->count();
        //待审商品
        $shop_goods = Db::name('shop_goods')->where(['checked'=>0])->where($where)->count();
        //待审认证
        $authenticate = Db::name('authenticate')->where(['checked'=>0])->where($where)->count();
        //待审案例
        $cases = Db::name('cases')->where(['checked'=>0])->where($where)->count();
        //待审文章
        $article = Db::name('article')->where(['checked'=>0])->where($where)->count();
        //待审公告
        $notice = Db::name('notice')->where(['checked'=>0])->where($where)->count();
        //今日预约技工
        $app_mechanic = Db::name('appointment')->where(['type'=>1])->where(['UNIX_TIMESTAMP(create_time)'=>['>=',strtotime(date('Y-m-d'))]])->where(['UNIX_TIMESTAMP(create_time)'=>['<',strtotime(date('Y-m-d',strtotime('+1 day')))]])->where($where)->count();
        //今日预约工长
        $app_gz = Db::name('appointment')->where(['type'=>2])->where(['UNIX_TIMESTAMP(create_time)'=>['>=',strtotime(date('Y-m-d'))]])->where(['UNIX_TIMESTAMP(create_time)'=>['<',strtotime(date('Y-m-d',strtotime('+1 day')))]])->where($where)->count();
        //今日预约设计师
        $app_designer = Db::name('appointment')->where(['type'=>3])->where(['UNIX_TIMESTAMP(create_time)'=>['>=',strtotime(date('Y-m-d'))]])->where(['UNIX_TIMESTAMP(create_time)'=>['<',strtotime(date('Y-m-d',strtotime('+1 day')))]])->where($where)->count();
        //今日预约装饰公司
        $app_company = Db::name('appointment')->where(['type'=>4])->where(['UNIX_TIMESTAMP(create_time)'=>['>=',strtotime(date('Y-m-d'))]])->where(['UNIX_TIMESTAMP(create_time)'=>['<',strtotime(date('Y-m-d',strtotime('+1 day')))]])->where($where)->count();
        //今日商城订单
        $order = Db::name('order')->where(['UNIX_TIMESTAMP(create_time)'=>['>=',strtotime(date('Y-m-d'))]])->where(['UNIX_TIMESTAMP(create_time)'=>['<',strtotime(date('Y-m-d',strtotime('+1 day')))]])->where($where)->count();
        //今日加盟申请
        $joining = Db::name('joining')->where(['UNIX_TIMESTAMP(create_time)'=>['>=',strtotime(date('Y-m-d'))]])->where(['UNIX_TIMESTAMP(create_time)'=>['<',strtotime(date('Y-m-d',strtotime('+1 day')))]])->where(['checked'=>0])->count();
        //今日信息发布
        $question = Db::name('question')->where(['UNIX_TIMESTAMP(create_time)'=>['>=',strtotime(date('Y-m-d'))]])->where(['UNIX_TIMESTAMP(create_time)'=>['<',strtotime(date('Y-m-d',strtotime('+1 day')))]])->where($where)->count();

        $this->assign('mechanic',$mechanic);
        $this->assign('gongzhang',$gongzhang);
        $this->assign('designer',$designer);
        $this->assign('company',$company);
        $this->assign('shop',$shop);
        $this->assign('shop_goods',$shop_goods);
        $this->assign('authenticate',$authenticate);
        $this->assign('cases',$cases);
        $this->assign('article',$article);
        $this->assign('notice',$notice);
        $this->assign('app_mechanic',$app_mechanic);
        $this->assign('app_gz',$app_gz);
        $this->assign('app_designer',$app_designer);
        $this->assign('app_company',$app_company);
        $this->assign('order',$order);
        $this->assign('joining',$joining);
        $this->assign('question',$question);

        $this->assign('user',$user);
        return $this->fetch();
    }

}
