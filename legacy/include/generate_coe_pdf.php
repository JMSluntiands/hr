<?php

/**
 * COE: build a filled Certificate of Employment PDF (Dompdf) from document_requests + employees (+ compensation when present).
 */

/** @var string Last failure reason (plain text); consumed by `coe_pdf_take_failure_message()`. */
$coePdfGenerationFailure = '';

function coe_pdf_fail(string $message): void
{
    global $coePdfGenerationFailure;
    $coePdfGenerationFailure = $message;
    error_log('COE PDF: ' . $message);
}

/**
 * Plain-text detail for the last failed COE PDF attempt (then cleared).
 */
function coe_pdf_take_failure_message(): string
{
    global $coePdfGenerationFailure;
    $m = (string)$coePdfGenerationFailure;
    $coePdfGenerationFailure = '';
    return $m;
}

function coe_employees_has_gender_column(mysqli $conn): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $r = @$conn->query("SHOW COLUMNS FROM `employees` LIKE 'gender'");
    $cache = ($r && $r->num_rows > 0);
    return $cache;
}

function coe_document_type_is_coe(string $documentType): bool
{
    $t = trim($documentType);
    if ($t === '') {
        return false;
    }
    if (strcasecmp($t, 'COE') === 0) {
        return true;
    }
    $l = strtolower($t);
    return strpos($l, 'certificate of employment') !== false;
}

/**
 * @return array<string, mixed>
 */
function coe_pdf_branding(): array
{
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'coe_pdf_branding.php';
    if (is_file($path)) {
        $b = require $path;
        if (is_array($b)) {
            return $b;
        }
    }
    return [
        'accent' => '#E85D04',
        'accent_dark' => '#C2410C',
        'company_primary' => 'LUNTIAN BUILDING DESIGN SOLUTIONS',
        'company_secondary' => 'COOLAI DRAFTING SERVICES',
        'tagline' => '• ENERGY EFFICIENCY • BUILDING DESIGN • DRAFTING • RENDERING • VR •',
        'contact_lines' => [],
        'employer_name_cert' => 'the Company',
        'signatory_name' => '',
        'signatory_title' => '',
        'footer_company' => '',
        'footer_address' => '',
        'footer_registration' => '',
    ];
}

function coe_format_long_date(?string $ymd): string
{
    if ($ymd === null || $ymd === '') {
        return '';
    }
    $t = strtotime($ymd);
    if ($t === false) {
        return $ymd;
    }
    return date('F d, Y', $t);
}

/**
 * @return array{0:string,1:string} subject, possessive for sentences
 */
function coe_pronouns(?string $gender): array
{
    $g = strtolower(trim((string)$gender));
    if ($g === 'female') {
        return ['She', 'her'];
    }
    if ($g === 'male') {
        return ['He', 'his'];
    }
    return ['They', 'their'];
}

/**
 * @return ?array{basic: float|null, it_allowance: float|null}
 */
function coe_load_compensation(mysqli $conn, int $employeeId): ?array
{
    $t = $conn->query("SHOW TABLES LIKE 'employee_compensation'");
    if (!$t || $t->num_rows === 0) {
        return null;
    }
    $eid = (int)$employeeId;
    $sql = 'SELECT basic_salary_monthly, allowance_internet FROM employee_compensation WHERE employee_id = ' . $eid . ' LIMIT 1';
    $res = $conn->query($sql);
    if (!$res) {
        return null;
    }
    $row = $res->fetch_assoc();
    if (!$row) {
        return null;
    }
    $basic = $row['basic_salary_monthly'];
    $it = $row['allowance_internet'] ?? null;
    return [
        'basic' => $basic !== null && $basic !== '' ? (float)$basic : null,
        'it_allowance' => $it !== null && $it !== '' ? (float)$it : null,
    ];
}

function coe_money_php(?float $n): string
{
    if ($n === null) {
        return '';
    }
    return number_format($n, 2, '.', ',') . ' PHP';
}

/**
 * Writes uploads/employee_documents/{employeeId}_req{requestId}_coe_gen_{time}.pdf
 * Returns relative path under uploads/ (e.g. employee_documents/...) or null.
 */
function generate_coe_pdf_for_document_request(mysqli $conn, int $employeeId, int $requestId): ?string
{
    require_once __DIR__ . '/ensure_document_requests_coe_columns.php';
    ensure_document_requests_coe_columns($conn);

    $autoload = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    if (!is_file($autoload)) {
        coe_pdf_fail('vendor/autoload.php not found at ' . $autoload);
        return null;
    }
    require_once $autoload;
    if (!class_exists(\Dompdf\Dompdf::class)) {
        coe_pdf_fail('Dompdf class not found after autoload (run `composer install` in project root).');
        return null;
    }

    $eid = (int)$employeeId;
    $rid = (int)$requestId;
    if ($eid <= 0 || $rid <= 0) {
        coe_pdf_fail('Invalid employee or request id.');
        return null;
    }

    $genderCol = coe_employees_has_gender_column($conn) ? 'e.gender' : 'NULL AS gender';
    // mysqli_stmt::get_result() needs mysqlnd; plain query avoids XAMPP setups without it.
    $sql = 'SELECT dr.id, dr.document_type, dr.coe_purpose, dr.coe_include_salary, dr.approved_at, dr.created_at,
            e.full_name, e.position, e.department, e.date_hired, ' . $genderCol . '
            FROM document_requests dr
            INNER JOIN employees e ON e.id = dr.employee_id
            WHERE dr.id = ' . $rid . ' AND dr.employee_id = ' . $eid . " AND LOWER(TRIM(CAST(dr.status AS CHAR))) = 'approved' LIMIT 1";
    $res = $conn->query($sql);
    if (!$res) {
        coe_pdf_fail('SQL error: ' . $conn->error);
        return null;
    }
    $row = $res->fetch_assoc();
    if (!$row) {
        coe_pdf_fail('No approved COE row found for this request and employee (check document_requests.status and employee_id).');
        return null;
    }
    if (!coe_document_type_is_coe((string)($row['document_type'] ?? ''))) {
        coe_pdf_fail('Request is not a COE type (document_type=' . ($row['document_type'] ?? '') . ').');
        return null;
    }

    $brand = coe_pdf_branding();
    $accent = htmlspecialchars((string)($brand['accent'] ?? '#E85D04'), ENT_QUOTES, 'UTF-8');

    $fullName = trim((string)($row['full_name'] ?? ''));
    $position = trim((string)($row['position'] ?? ''));
    $department = trim((string)($row['department'] ?? ''));
    $dateHired = coe_format_long_date((string)($row['date_hired'] ?? ''));
    $purpose = trim((string)($row['coe_purpose'] ?? ''));
    $includeSalary = strtoupper(trim((string)($row['coe_include_salary'] ?? ''))) === 'YES';

    [$subj, $poss] = coe_pronouns($row['gender'] ?? null);
    $employerCert = trim((string)($brand['employer_name_cert'] ?? 'the Company'));
    if ($employerCert === '') {
        $employerCert = 'the Company';
    }

    $posLine = $position !== '' ? htmlspecialchars($position, ENT_QUOTES, 'UTF-8') : 'as assigned';
    $deptLine = $department !== '' ? htmlspecialchars($department, ENT_QUOTES, 'UTF-8') : '';
    $deptPhrase = $deptLine !== ''
        ? 'within the <strong>' . $deptLine . '</strong> department'
        : 'within the organization';

    $issueRaw = trim((string)($row['approved_at'] ?? ''));
    $issueDate = $issueRaw !== '' ? coe_format_long_date(substr($issueRaw, 0, 10)) : coe_format_long_date(date('Y-m-d'));

    $p1 = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8')
        . ' is a regular employee of <strong>' . htmlspecialchars($employerCert, ENT_QUOTES, 'UTF-8') . '</strong>'
        . ($dateHired !== '' ? ' since <strong>' . htmlspecialchars($dateHired, ENT_QUOTES, 'UTF-8') . '</strong>' : '')
        . '. ' . htmlspecialchars($subj, ENT_QUOTES, 'UTF-8') . ' currently holds the position of <strong>' . $posLine
        . '</strong> ' . $deptPhrase . '.';

    $p2 = '';
    if ($includeSalary) {
        $comp = coe_load_compensation($conn, $eid);
        $gross = $comp && isset($comp['basic']) && $comp['basic'] !== null ? coe_money_php($comp['basic']) : '';
        $it = $comp && isset($comp['it_allowance']) && $comp['it_allowance'] !== null ? coe_money_php($comp['it_allowance']) : '';
        if ($gross !== '' || $it !== '') {
            $p2 = 'As of the date of this issuance, ' . htmlspecialchars($poss, ENT_QUOTES, 'UTF-8') . ' current gross monthly compensation is <strong>'
                . ($gross !== '' ? $gross : 'on record with HR') . '</strong>';
            if ($it !== '') {
                $p2 .= ', inclusive of IT allowance worth <strong>' . $it . '</strong>';
            }
            $p2 .= '.';
        } else {
            $p2 = 'Compensation figures may be provided separately upon verification, as recorded in the employer\'s payroll.';
        }
    }

    $p3 = 'This certification is being issued upon the request of <strong>' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . '</strong>';
    if ($purpose !== '') {
        $p3 .= ' for the purpose of <strong>' . htmlspecialchars($purpose, ENT_QUOTES, 'UTF-8') . '</strong>';
    }
    $p3 .= '.';

    $contactHtml = '';
    $lines = $brand['contact_lines'] ?? [];
    if (is_array($lines)) {
        foreach ($lines as $ln) {
            $ln = trim((string)$ln);
            if ($ln === '') {
                continue;
            }
            $contactHtml .= '<span style="margin-right:14px;">' . htmlspecialchars($ln, ENT_QUOTES, 'UTF-8') . '</span>';
        }
    }

    $sigName = htmlspecialchars(trim((string)($brand['signatory_name'] ?? '')), ENT_QUOTES, 'UTF-8');
    $sigTitle = htmlspecialchars(trim((string)($brand['signatory_title'] ?? '')), ENT_QUOTES, 'UTF-8');
    $footCo = htmlspecialchars(trim((string)($brand['footer_company'] ?? '')), ENT_QUOTES, 'UTF-8');
    $footAddr = htmlspecialchars(trim((string)($brand['footer_address'] ?? '')), ENT_QUOTES, 'UTF-8');
    $footReg = htmlspecialchars(trim((string)($brand['footer_registration'] ?? '')), ENT_QUOTES, 'UTF-8');

    $coPrim = htmlspecialchars((string)($brand['company_primary'] ?? ''), ENT_QUOTES, 'UTF-8');
    $coSec = htmlspecialchars((string)($brand['company_secondary'] ?? ''), ENT_QUOTES, 'UTF-8');
    $tag = htmlspecialchars((string)($brand['tagline'] ?? ''), ENT_QUOTES, 'UTF-8');

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">'
        . '<style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #111827; margin: 0; padding: 24px 32px; }
            .bar-top { height: 6px; background: ' . $accent . '; margin: -24px -32px 0 -32px; }
            .hdr { margin-top: 14px; text-align: center; }
            .co1 { font-size: 13pt; font-weight: bold; color: ' . $accent . '; letter-spacing: 0.5px; }
            .co2 { font-size: 11pt; font-weight: bold; color: #9ca3af; margin-top: 2px; letter-spacing: 0.5px; }
            .tagbar { margin-top: 10px; background: ' . $accent . '; color: #fff; font-size: 7.5pt; padding: 5px 8px; text-align: center; letter-spacing: 0.3px; }
            .contact { margin-top: 8px; font-size: 7.5pt; color: #1d4ed8; text-align: center; line-height: 1.5; }
            .bar-mid { height: 4px; background: ' . htmlspecialchars((string)($brand['accent_dark'] ?? '#C2410C'), ENT_QUOTES, 'UTF-8') . '; margin: 12px -32px 0 -32px; }
            h1 { text-align: center; font-size: 13pt; font-weight: bold; margin: 28px 0 18px 0; letter-spacing: 1px; }
            .date { margin-bottom: 14px; }
            p { line-height: 1.55; text-align: justify; margin: 0 0 12px 0; }
            .sign { margin-top: 36px; }
            .sig-name { font-weight: bold; margin-top: 40px; }
            .footer { margin-top: 48px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 8pt; color: #4b5563; text-align: center; line-height: 1.45; }
        </style></head><body>'
        . '<div class="bar-top"></div>'
        . '<div class="hdr"><div class="co1">' . $coPrim . '</div><div class="co2">' . $coSec . '</div></div>'
        . '<div class="tagbar">' . $tag . '</div>'
        . ($contactHtml !== '' ? '<div class="contact">' . $contactHtml . '</div>' : '')
        . '<div class="bar-mid"></div>'
        . '<h1>CERTIFICATE OF EMPLOYMENT</h1>'
        . '<div class="date">' . htmlspecialchars($issueDate, ENT_QUOTES, 'UTF-8') . '</div>'
        . '<p>To whom it may concern:</p>'
        . '<p>This is to certify that ' . $p1 . '</p>'
        . ($p2 !== '' ? '<p>' . $p2 . '</p>' : '')
        . '<p>' . $p3 . '</p>'
        . '<div class="sign">'
        . '<div class="sig-name">' . $sigName . '</div>'
        . '<div>' . $sigTitle . '</div>'
        . '</div>'
        . '<div class="footer">'
        . ($footCo !== '' ? '<div><strong>' . $footCo . '</strong></div>' : '')
        . ($footAddr !== '' ? '<div>' . $footAddr . '</div>' : '')
        . ($footReg !== '' ? '<div>' . $footReg . '</div>' : '')
        . '</div>'
        . '</body></html>';

    try {
        $root = realpath(dirname(__DIR__)) ?: dirname(__DIR__);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('chroot', $root);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfOut = $dompdf->output();
    } catch (Throwable $e) {
        coe_pdf_fail('Dompdf: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
        return null;
    }

    if ($pdfOut === false || $pdfOut === '') {
        coe_pdf_fail('Dompdf returned empty PDF output.');
        return null;
    }

    $employeeDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'employee_documents';
    if (!is_dir($employeeDir) && !mkdir($employeeDir, 0755, true)) {
        coe_pdf_fail('Could not create directory: ' . $employeeDir);
        return null;
    }
    $basename = $eid . '_req' . $rid . '_coe_gen_' . time() . '.pdf';
    $destAbs = $employeeDir . DIRECTORY_SEPARATOR . $basename;
    if (@file_put_contents($destAbs, $pdfOut) === false) {
        coe_pdf_fail('Could not write PDF file: ' . $destAbs);
        return null;
    }
    return 'employee_documents/' . $basename;
}
