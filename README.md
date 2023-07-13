### Incremenet id update guide

The file interceptor.php under app/code/generated/Controller/Ipn/Callback should be removed as Callback class has new constructor defined.

**************************
## How to use


假设web服务器用户为www，magento的安装根目录为/data/wwwroot/magento
判断magento根目录下，请确认目录下有app,bin,composer.json


1. switch to magento install directory

```bash
cd /data/wwwroot/magento
```

2. install qfpay crypto gateway module

```bash
mkdir -p app/code/QFPay/PaymentGateway/
cd app/code/QFPay/PaymentGateway/
cp magento-secure-checkout-v2.4.1.zip
unzip magento-secure-checkout-v2.4.1.zip
cd /data/wwwroot/magento
chown -R www:www app/code
chmod -R 755 app/code
```

```bash
php bin/magento module:status
php bin/magento module:enable QFPay_PaymentGateway
php bin/magento setup:upgrade
php bin/magento cache:clean
```

3. configure the crobtab jobs

```bash
crontab  -e
5 * * * * /usr/bin/curl http://你的网址/PaymentGateway/ipn/index
service crond start
```

4. payment plugin configuration

Admin管理后台->Stores->Configuration->SALES->Payment Methods->QFPay Payment Gateway 可以设置插件的相关参数



#   Q F p a y  
 #   Q F p a y  
 