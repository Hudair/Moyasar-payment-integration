<?php

if ( ! defined( 'ABSPATH' ) ) {
   exit; // Exit if accessed directly
}

class Indatos_Authorizenetbasic_Utils
{
   public function __construct() 
   {  


      add_action( 'admin_menu', array(&$this,'authnetpro_tx_transaction_page_menu') );
   }



   function authnetpro_tx_transaction_page_menu()
   {

      add_menu_page( 
         __( 'Authorize.net Recent Transactions', 'authprowoo' ),
         'Authorize.net Transactions',
         'manage_options',
         'authnetbasic_tx_transaction_explorer',
         array(&$this,'authnetbasic_tx_transaction_explorer_callback'),
         plugins_url( 'authorizenet-payment-gateway-for-woocommerce/images/transaction_icon.svg' ),
         6
      ); 



   }

   function authnetbasic_tx_transaction_explorer_callback(){

     $transaction_status = array(
         'authorizedPendingCapture'    => 'Authorized and Pending Capture.',
         'capturedPendingSettlement'   => 'Authorized & Captured. Pending Settlement.',
         'communicationError'          => 'Communication Error.',
         'refundSettledSuccessfully'   => 'Refund Settled Successfully.',
         'refundPendingSettlement'     => 'Refunded, And Pending Settlement.',
         'approvedReview'              => 'Approved Mannual Review.',
         'declined'                    => 'Declined.',
         'couldNotVoid'                => 'Could Not Void - Check Authorize.net account for more details.',
         'expired'                     => 'Expired',
         'generalError'                => 'General Error',
         'failedReview'                => 'Failed Manual Review',
         'settledSuccessfully'         => 'Settled Successfully',
         'settlementError'             => 'Settlement Error',
         'underReview'                 => 'Under Review - Please review from Authorize.net account dashboard.',
         'voided'                      => 'Transaction Voided or Refunded before settlement.',
         'FDSPendingReview'            => 'Pending  Mannual Review - Fraud Detection Suite',
         'FDSAuthorizedPendingReview'  => 'Card is Authorized But Pending Mannual Review - Fraud Detection Suite',
         'returnedItem'                => 'Returned Item',
       );
      
      echo '<h2>Last 50 Unsettled Authorize.net Transactions</h2>';
      $data_response =  $this->get_recent_transactions();
      $currency = get_woocommerce_currency_symbol();
      $html = '';
      if( $data_response->messages->message->code == 'I00001' ){
         $html .= '<div class="wrap"><table class="wp-list-table widefat fixed striped table-view-list">';
      $html .= '<thead><tr>';
      $html .= '<td>Invoice/Order#</td>';
      $html .= '<td>Transaction Status</td>';
      $html .= '<td>First Name</td>';
      $html .= '<td>Last Name</td>';
      $html .= '<td>Type</td>';
      $html .= '<td>Last 4</td>';
      $html .= '<td>Settlement Amount</td>';
      $html .= '</tr></thead><tbody>';
         $transactions = $data_response->transactions->transaction;

         foreach($transactions as $single_transaction){
            $html .= '<tr>';
            $html .= '<td>'.$single_transaction->invoiceNumber.'</td>';
            $html .= '<td>'.$transaction_status[(string)$single_transaction->transactionStatus].'<br/>Transaction ID: '.$single_transaction->transId.'</td>';
            $html .= '<td>'.$single_transaction->firstName.'</td>';
            $html .= '<td>'.$single_transaction->lastName.'</td>';
            $html .= '<td>'.$single_transaction->accountType.'</td>';
            $html .= '<td>'.$single_transaction->accountNumber.'</td>';
            $html .= '<td>'.$currency.''.$single_transaction->settleAmount.'</td>';
            $html .= '</tr>';
         }
         $html .= '</tbody></table></div><p>Note: It takes 1-2 mins for recent transactions to appear here. If you are using same Authorize.net account with multiple sites or softwares, then order numbers might not match with data on this site for some records, if transaction happend on other platform or website.</p><p>More features coming soon</p>';
      }else{
       $html.= "Unable to get transaction list. Please check if API details are correct. Error code:".$data_response->messages->message->code;
      }

      echo $html;
   }

   function get_recent_transactions(){

      $wc_auth = new WC_Tech_Autho();



      if($wc_auth->mode == 'true'){
         $process_url          = 'https://apitest.authorize.net/xml/v1/request.api';
      }
      else{
         $process_url          = 'https://api.authorize.net/xml/v1/request.api';
      }

      $xml = '<getUnsettledTransactionListRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
               <merchantAuthentication>
                  <name>'.$wc_auth->login.'</name>
                  <transactionKey>'.$wc_auth->transaction_key.'</transactionKey>
                  </merchantAuthentication>
                <sorting>
                  <orderBy>submitTimeUTC</orderBy>
                  <orderDescending>true</orderDescending>
                </sorting>
                <paging>
                  <limit>50</limit>
                  <offset>1</offset>
                </paging>
            </getUnsettledTransactionListRequest>';

      $headers = array(
         "Content-type: text/xml",
         "Content-length: ". strlen($xml),
         "Connection: close"
      );

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $process_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      $data = curl_exec($ch);
      curl_close($ch);

      $respone = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOWARNING);

      return $respone;

   }


}