<?php 
require('/home/cloudpanel/htdocs/www.americanswan.com/current/app/Mage.php');
ini_set('display_errors', 1);

try{
		$html = "sample mail";
                $date = date('d-M-Y');
                $config = array('ssl' => 'tls',
                                'auth' => 'login',
				'port' => '587',
                                        #'username' => 'AKIAIUW2C4SD3N6FLUKA',
                                        #'password' => 'dGFssjyc93xR8e48rJ4F38O6l4vBcSyXpbEadFlN');
					'username' => 'AKIAJ3Z55BLHVVKK6WHQ',
					'password' => 'Aik7+O1z7U8XgoMu3sItIG2Yb1CKrn3EnkRxykXvRfZK');
//                              'username' => 'AKIAITUWZAQIS7Y7WQ7A',
//                              'password' => 'AmyITfGRlnjBQSeJZuvqJ3J2mWO1Hx91nusF19ggmOq9');
                $transport = new Zend_Mail_Transport_Smtp('email-smtp.us-east-1.amazonaws.com', $config);
                $mail = new Zend_Mail();
                $mail->setFrom("service@americanswan.com","Report");
                            $mail->addTo("tejprakash@intelligrape.com","navjots@intelligrape.com");
                            $mail->setSubject("Daily Best Seller Report - TOP 20 | $date");
                            $mail->setBodyHtml($html);
                            $mail->send($transport);
                            echo "mail sent"."\n";

    }catch(Exception $e)
    {
        echo $e->getMessage();
            echo "mail sending error";
	    echo "$e";
    }
?>

