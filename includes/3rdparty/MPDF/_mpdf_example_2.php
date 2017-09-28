<?

die;


$mpdf = MPDFLibrary::create();
$mpdf->charset_in = "utf8";

$stylesheet = file_get_contents("{$_SERVER['DOCUMENT_ROOT']}/static/css/pdf_resume.css");
$mpdf->WriteHTML($stylesheet, 1);

$html = new View('accounts/page-user_resume_pdf', compact('user', 'achievement_list', 'edu_places', 'job_places', 'events'));
$mpdf->WriteHTML($html->fetch(), 2);

$mpdf->Output();
//        $mpdf->Output("resume_" . Meta::getTranslit($user->name, 25) . "_" . date("Y") . ".pdf", "D");
?>