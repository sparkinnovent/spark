<?php
require_once './Dbcontroller.php';
require_once './Util.php';
require_once './admin/Usersession.php';
require_once './vendor/autoload.php';
require_once './Mail.php';

$db_handle = new DBController();
$util = new Util();

if (isset($_GET["mail"])) {

    $cert_no = uniqid();

    $id = $db_handle->clean($_GET["id"]);
    $email = $db_handle->clean($_GET["mail"]);

    $sql = "SELECT * FROM project WHERE id='$id' AND email='$email'";
//echo $sql;
    $result = $db_handle->runBaseQuery($sql);

    if (!empty($result)){

        foreach ($result as $key => $value) {

            $email = $result[0]["email"];            
            $title = $result[0]["title"];
            $cat_iid = $result[0]["cat_iid"];
            $cat_iid = $util->getCategory($cat_iid);
            $status = $result[0]["status"];
        }//end of foreach ($result as $key => $value)


        $sql2 = "SELECT * FROM project_member WHERE project_id='$id' ORDER BY position ASC";
        $result2 = $db_handle->run($sql2);

        $member_new = "";
        if(!empty($result2)){

            while ($row2 = mysqli_fetch_assoc($result2)) {
                $name = $row2["name"];
            //$member_new = $member_new . "<br>";
                $member_new = $member_new. $name. "<br>";


                if (!isset($email_list)) {
                    $email_list = array($row2["email"]);
                }else{
                    array_push($email_list, $row2["email"]);
                }
            }

        }//end of if(!empty($result2))


        class MYPDF extends TCPDF {

    //Page header
            public function Header() {
        // set bacground image
                $img_file = K_PATH_IMAGES.'gdiid-loa-header-2021.png';
                $this->Image($img_file, 5, 0, 200, '', 'PNG', '', 'T', false, 200, '', false, false, 1, false, false, false);
        // restore auto-page-break status
        //$this->SetAutoPageBreak($auto_page_break, $bMargin);
        // set the starting point for the page content
        //$this->setPageMark();
            }
        } //end of class MYPDF extends TCPDF



// create new PDF document

        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
// set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('GDIID 2021');
        $pdf->SetTitle('GDIID 2021 - Letter Of Acceptance');
        $pdf->SetSubject('GDIID 2021 - LOA'); 
//$pdf->SetKeywords('TCPDF, PDF, example, test, guide');
// set default header data
//$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 049', PDF_HEADER_STRING);
// set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
// set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->setPrintHeader(true);
// set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, 35, 25);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);
        // set auto page break
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // --------------------------------------------------------
        // set font
        $pdf->SetFont('helvetica', '', 9);
        // add a page
        $pdf->AddPage();
        //$pdf->setXY(70, 75);
        $ref = "Our Reference: 600-UiTMCTKT (PJI. 5/1)<BR>DATE: ".date("d M Y");
        $pdf->writeHTMLCell($w=0, $h=0, $x=120, $y=25, $ref, $border=0, $ln=1, $fill=0, $reseth=false, $align='', $autopadding=true);
        $pdf->SetFont('helvetica', '', 11);
        //$html = "<p>ID NO: ".$paper_reg_id."</p>";
        $html = "<p>";
        $html = $html.$member_new;
        //$html = "<p><b>".strtoupper($lect_name)."<BR>".strtoupper($lect_jawatan)."</b>";
        $html = $html."</p>";
        //$pdf->writeHTML($html);
        $html2 = $html. "<p>Dear Prof./Assoc. Prof/ Dr./Sir/Madam,</p>";
        $pdf->writeHTML($html2, true, false, false, false, '');
        $pdf->setXY(0, 60);
        $html_style = "
        <!-- EXAMPLE OF CSS STYLE -->
        <style>
        p.first{
            text-align:justify;
            text-justify:inter-word;
        }
        p.last{
            font-style: italic;
            font-size: 10px;
            text-align:justify;
            text-justify:inter-word;
        }
        ol.k {
            list-style-type: lower-alpha;
            text-align:justify;
            text-justify:inter-word;
        }
        </style>
        ";
        $html_loa_header = '<br><p class="first"><b>LETTER OF ACCEPTANCE</b></p>';
        $message_email = $html31 . '<p class="first"><b>Dear Author(s),</b></p>';

        $html3 = $html3. '<p class="first">We are pleased to inform you that your paper;</p>';

        $html3 = $html3. '<table>';

        $html3 = $html3. '
        <tr>
        <td width="60">Project ID</td>
        <td width="10">:</td>
        <td width="450" ><b>'.$id.'</b></td>
        </tr>
        <tr>
        <td width="60">Title</td>
        <td width="10">:</td>
        <td width="450" ><b>'.$title.'</b></td>
        </tr>
        <tr>
        <td width="60">Category</td>
        <td width="10">:</td>
        <td width="450" ><b>'.$cat_iid.'</b></td>
        </tr>';

        $html3 = $html3. '</table>';

        $html3 = $html3. '<p class="first">has been <b>ACCEPTED</b> to be presented at the <b>Graduate Digital Invention, Innovation and Design (GDIID) 2021</b> which will be held from <b>11 April 2021 to 11 July 2021</b></p>';

        $html3 = $html3. '<p class="first">2.   The payment details are available on our website: <a href="https://gdiid.uitm.edu.my" >https://gdiid.uitm.edu.my</a>. The payment can be made in MYR (Malaysian Ringgit) for Malaysian participants and USD (US Dollar) for international participants via UiTM FinePay at <a href="https://bit.ly/GDIID2021PAY">https://bit.ly/GDIID2021PAY</a></p>';

        $html3 = $html3. '<p class="first">3.   Please upload the proof of payment on our website. Those who cancel the participation after the registration date are required to pay the whole amount of the invoiced fee and kindly note that it is non-refundable. Please be informed that the secretariats reserve the right to reject any application that does not meet the stipulated payment requirements.</p>';

        $html3 = $html3. '<p class="first">4.   Upon payment confirmation, all projects that have been accepted will be published on our YouTube channel. Kindly ensure that your project follows the submission guidelines and please fill in the video submission form which is also available on our website before <b>5 August 2021.</b></p>';

        $html3 = $html3. '<p class="first">5.   Accepted extended abstract will be published in e-proceeding, provided that amendments are made based on the reviewer\'s comments. Please note that it is the author\'s responsibility to ensure the camera-ready extended abstract is revised before the final submission by <b>5 August 2021</b> and must be submitted together with the <b>Turnitin report of less than 30 percent (%) similarity index.</b></p>';

        $html_loa_text_1 = $html_style.$html_loa_header.$html3;

        $pdf->writeHTML($html_loa_text_1, true, false, false, false, '');
        $pdf->AddPage();

        $html4 =  '<p class="first">6.   For further enquiries, please do not hesitate to contact us via the attached email address. Congratulations on the acceptance of your project. Please visit our website for the latest updates.</p>';


/*$html3 = $html3. '<p class="first">3. Please pay your fee according to your event and category; </p>';

$html3 = $html3. '<table style="width:100%;border-width:1px;border-style:solid;border-color:black;">';

$html3 = $html3. '
<tr>
<td width="250"><b>Registration Fees</b></td>
<td width="100"><b>Local  (MYR)</b></td>
<td width="100"><b>International (USD)</b></td>
</tr>
<tr>
<td width="250">Presenter and IOP Publication Fees</td>
<td width="100">MYR 550</td>
<td width="100" >USD 220</td>
</tr>
<tr>
<td width="250">Student Presenter and IOP Publication Fees</td>
<td width="100">MYR 500</td>
<td width="100" >USD 170</td>
</tr>';

$html3 = $html3. '</table>';*/
// $html3 = $html3. '<ol class="k">';
// $html3 = $html3. "<li>";
// $html3 = $html3. "Registration by Malaysia participants is payable using the MYR (Malaysia Ringgit) currency through Local Order (LO)/ Banker’s Cheque/ Electronic Fund Transfer. (Please write a note of your name or participation ID)";
// $html3 = $html3. "</li>";
// $html3 = $html3. "<li>";
// $html3 = $html3. "Registration by international participants, payment to be made in USD (US Dollar) in the form of Banker’s Cheque / Telegraphic Transfer. (Please quote name and country of origin at the back of your banker’s cheque).";
// $html3 = $html3. "</li>";
// $html3 = $html3. "</ol>";
/*$html3 = $html3. '<p class="first">a)   Registration by Malaysia participants is payable using the MYR (Malaysia Ringgit) currency through Local Order (LO)/ Banker’s Cheque/ Electronic Fund Transfer. (Please write a note of your name or participation ID)</p>';

$html3 = $html3. '<p class="first">b)   Registration by international participants, payment to be made in USD (US Dollar) in the form of Banker’s Cheque / Telegraphic Transfer. (Please quote name and country of origin at the back of your banker’s cheque). </p>';*/

// $html3 = $html3. '<p class="first">Payment should be payable to:</p>';

// $html3 = $html3. '<table>';

// $html3 = $html3. '
// <tr>
// <td width="150"><b>Payment Reference</b></td>
// <td width="10"><b>:</b></td>
// <td width="300"><b>'.$paper_reg_id.'</b></td>
// </tr>
// <tr>
// <td width="150">Fees</td>
// <td width="10">:</td>
// <td width="300" >'.$fee_currency.$fee_amount.'</td>
// </tr>
// <tr>
// <td width="150">Beneficiary Name</td>
// <td width="10">:</td>
// <td width="300" >UNIVERSITI TEKNOLOGI MARA (UITM)</td>
// </tr>
// <tr>
// <td width="150">Bank Name</td>
// <td width="10">:</td>
// <td width="300" >Bank Islam Malaysia Berhad, Dungun Branch, Terengganu, Malaysia</td>
// </tr>
// <tr>
// <td width="150">Account No</td>
// <td width="10">:</td>
// <td width="300" >1304 4010 0000 11</td>
// </tr>
// <tr>
// <td width="150">Swift Route Code</td>
// <td width="10">:</td>
// <td width="300" >BIMBMYKL</td>
// </tr>';

// $html3 = $html3. '</table>';

// $html3 = $html3. '<p class="first">3.   Participants whose registration have been accepted are obliged to pay the total amount of fee according to conference policy. Participants who cancel the participation after the last registration date must pay the whole amount of the invoiced fee. The fee is non-refundable. Please upload the proof of payment at https://temic-uitm.com. and kindly refer to our website for details of the payment. Please be informed that the organizers reserve the right to reject the participation of any application that does not meet stipulated payment requirements. </p>';

// $html3 = $html3. '<p class="first">4.   Please do not hesitate to contact us directly via the e-mail address below if you have any questions. We congratulate you on the acceptance of your paper and we look forward to seeing you virtually in TeMIC2012. Please visit our website for any latest updates. </p>';

$html4 = $html4. '<p>Thank you. </p>';
$html4 = $html4. '<p>Best Regards,</p>';
//$html3 = $html3. '<img src="images/Sign-KPP.png" alt="sign" width="100" border="0" />';
$html4 = $html4. '<p>Secretariat of GDIID2021<br>
Universiti Teknologi MARA Cawangan Terengganu<br>
Kampus Kuala Terengganu. 
Website: http://temic.uitm.edu.my/<br>
Email: urusetiagdiid2021@uitm.edu.my<br>
</p>';

$html4 = $html4. '<p class="last">Computer Generated. Signature is not required</p>';

$html_loa_text_2 = $html_style.$html4;

$pdf->writeHTML($html_loa_text_2, true, false, false, false, '');
//$pdf->writeHTMLCell($w=0, $h=0, $x=PDF_MARGIN_LEFT, $y='', $html31, $border=0, $ln=1, $fill=0, $reseth=false, $align='', $autopadding=true);

//$pdf->setXY(0, 100);
/*$html4 = "<p>Merujuk perkara di atas, sukacita dimaklumkan yang puan dilantik sebagai Penyelia/Pemeriksa bagi kursus ".$name." (Project Formulation) untuk Semester ".$event_sem.".</p>";
//$pdf->Write(5, $html4, '');
$pdf->writeHTML($html4, true, false, false, false, '');*/
$pdf->lastPage();
$file = 'LOA-'.$id.'-'.$cert_no.'.pdf';
$pdf->Output(__DIR__ . '/download/loa/'.$file, 'F');
//$pdf->Output($file, 'I');

//============================================================+
// END OF FILE
//============================================================+
//update status kat dalam db yang user ni dah download loa
$sql2 = "INSERT INTO project_loa (project_id, email, file) VALUES ('$id','$email', '$file')";
//cho $sql2;
$result2 = $db_handle->run($sql2);

if($status == "REGISTER" || $status == "LOA"){
    $sql3 = "UPDATE project SET status='LOA' WHERE id='$id'";
//echo $sql3;
    $result3 = $db_handle->run($sql3);

//sending email notification to the user
$message_email = $html2.$html_style.$html3.$html4;
$message_title = "WELCOME TO GDIID 2021";
$mailing = new Mail();
if (isset($email_list)) {

    foreach ($email_list as $key => $value) {
        $sendLoa = $mailing->sendNotify_w_file($message_title, $message_email, $value, $file, __DIR__ .'/download/loa/');
        sleep(1);
    }
}else{
    $sendLoa = $mailing->sendNotify_w_file($message_title, $message_email, $email, $file, __DIR__ .'/download/loa/');
}
//echo $sendLoa;
if ($sendLoa) {
    $util->redirect("./admin/dashboard.php?tab=tab1&success=notify");
}else{
    $util->redirect("./admin/dashboard.php?tab=tab1&fail=notify");
}
}
} //enf 

}//end if post