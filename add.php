<?
//usage, $1-site name $2-user $3-ssl_cert $4=ssl_bundle $5=ssl_key $6=force_ssl=1 or 2
  $site_name=$1;
  $user=$2;
  $key=$5;
$cert=$3;
$force_ssl=$6;
 $bundle=$4;
 if($key && $cert && $bundle) {   $ssl="yes"; } 
 $config_apache='<VirtualHost 127.0.0.1:8080>
        ServerAdmin com@'.$site_name.'
        ServerName '.$site_name.'
	ServerAlias '.$site_name.'
        DocumentRoot /home/'.$user.'/www/'.$site_name.'
        <Directory />
                Options FollowSymLinks
                AllowOverride All
        </Directory>
        <Directory /home/'.$user.'/www/'.$site_name.'>
                Options -Indexes FollowSymLinks MultiViews
                AllowOverride All
                Order allow,deny
                allow from all
        </Directory>


        ErrorLog /home/'.$user.'/logs/apache/error.log

        # Possible values include: debug, info, notice, warn, error, crit,
        # alert, emerg.
        LogLevel warn

        CustomLog /home/'.$user.'/logs/apache//access.log combined

        AddType application/x-httpd-php .php .php3 .php4 .php5 .phtml
        php_admin_value upload_tmp_dir "/home/'.$user.'/mod-tmp"
        php_admin_value session.save_path "/home/'.$user.'/mod-tmp"
</VirtualHost>';
  $config_nginx='server {'; if($ssl=="yes") { $config_nginx.="
listen *:443 ssl;
ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
"; }

$config_nginx.='
listen *:80; ## listen for ipv4
server_name '.$site_name.' www.'.$site_name.';
access_log /home/'.$user.'/logs/nginx/access.log;
# Перенаправление на back-end
location / {
proxy_pass http://127.0.0.1:8080/;
proxy_redirect http://127.0.0.1:8080/ /;
proxy_set_header Host $host;
proxy_set_header X-Real-IP $remote_addr;
proxy_set_header X-Forwarded-For $remote_addr;
proxy_connect_timeout 120;
proxy_send_timeout 120;
proxy_read_timeout 180;
}
# Статическиое наполнение отдает сам nginx
# back-end этим заниматься не должен
location ~* \.(jpg|jpeg|gif|png|ico|css|bmp|swf|js|txt)$ {
root /home/'.$user.'/www/'.$site_name.';
}
';
if($ssl=="yes") {$config_nginx.="
ssl_certificate /home/$user/ssl/$site_name/ssl.crt;
ssl_certificate_key /home/$user/ssl/$site_name/ssl.key;
";  }
if($force_ssl=="1") {
 $config_nginx.='if ($scheme = http) {
        return 301 https://$server_name$request_uri;
    }
';
}

$config_nginx.='
}
';


if($ssl=="yes") {exec("mkdir /home/$user/ssl/");
exec("mkdir /home/$user/ssl/$site_name");
exec("chmod 777 -R /home/$user/ssl");
exec("chown www-data:$user -R /home/$user/ssl/");
exec("cat $cert $bundle > /home/$user/ssl/$site_name/ssl.crt");
exec("cp $key /home/$user/ssl/$site_name/ssl.key");
 } 
exec("mkdir /home/$user/www");
exec("mkdir /home/$user/www/$site_name");
exec("mkdir /home/$user/logs");
exec("mkdir /home/$user/logs/apache");
exec("mkdir /home/$user/logs/nginx");
exec("mkdir /home/$user/mod-tmp");
exec("chown www-data:$user /home/$user/mod-tmp");
exec("chmod 777 -R /home/$user/mod-tmp");
exec("chown -R www-data:$user /home/$user/logs"); 
exec("chmod 777 -R /home/$user/logs");
exec("chown $user:$user /home/$user/www");
exec("chown $user:$user /home/$user/www/$site_name");


file_put_contents("/etc/nginx/sites-enabled/$site_name", $config_nginx);
file_put_contents("/etc/apache2/sites-enabled/$site_name", $config_apache);

exec("/etc/init.d/apache2 restart");
exec("/etc/init.d/nginx restart");

?>
