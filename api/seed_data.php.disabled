<?php
/**
 * AI Apollo — One-Time Dummy Data Seed Script
 * ─────────────────────────────────────────────────────────────────
 * Run once, then DELETE this file.
 * Access: /api/seed_data.php?token=<ADMIN_TOKEN from .env>
 * ─────────────────────────────────────────────────────────────────
 */

header('Content-Type: text/html; charset=utf-8');

// ── Token guard ───────────────────────────────────────────────────
$envFile = __DIR__ . '/../.env';
$envToken = '';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), 'ADMIN_TOKEN=')) {
            $envToken = trim(explode('=', $line, 2)[1]);
        }
    }
}
$provided = $_GET['token'] ?? '';
if (empty($envToken) || !hash_equals($envToken, $provided)) {
    http_response_code(403);
    die('<h1>403 — Forbidden</h1><p>Invalid or missing token.</p>');
}

require_once __DIR__ . '/db.php';

$pdo = get_db_connection();
$results = [];

// Insert only if the key doesn't already have data
function insertIfEmpty(PDO $pdo, string $type, string $key, array $data): bool {
    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if ($row) {
        $existing = json_decode($row['data'], true) ?: [];
        if (count($existing) > 0) return false; // Already has data
    }
    $pdo->prepare("
        INSERT INTO content (content_type, content_key, data)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
    ")->execute([$type, $key, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    return true;
}

// Read existing items, prepend new ones, write back
function appendItems(PDO $pdo, string $type, string $key, array $newItems): int {
    $stmt = $pdo->prepare("SELECT data FROM content WHERE content_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    $existing = $row ? (json_decode($row['data'], true) ?: []) : [];
    $merged = array_merge($newItems, $existing); // new items first
    $pdo->prepare("
        INSERT INTO content (content_type, content_key, data)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()
    ")->execute([$type, $key, json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    return count($merged);
}

// ══════════════════════════════════════════════════════════════════
// 1. MYTH BUSTERS
// ══════════════════════════════════════════════════════════════════
$myths = [
    [
        'statement' => 'Patients on a ventilator are completely unconscious and cannot hear anything.',
        'fact'      => 'Many mechanically ventilated patients are in a light sedation state and CAN hear. Families talking calmly, playing familiar music, and reassuring the patient have measurable positive effects on recovery. Family presence reduces delirium by up to 28%.',
        'source'    => 'ABCDEF Bundle Guidelines — Society of Critical Care Medicine (SCCM)'
    ],
    [
        'statement' => 'ECMO is experimental and almost never works.',
        'fact'      => 'At high-volume ECMO centres meeting ELSO criteria, survival rates reach 55–70% for severe respiratory failure. Apollo Ahmedabad is one of fewer than 10 centres in Gujarat with trained ECMO intensivists. It is a proven life-saving bridge therapy.',
        'source'    => 'ELSO Guidelines 2021 / EOLIA Trial NEJM 2018'
    ],
    [
        'statement' => 'Sepsis is just a really bad infection — antibiotics alone will fix it.',
        'fact'      => 'Sepsis is the body\'s life-threatening, dysregulated response to infection — it attacks every organ simultaneously. Early vasopressors, fluid resuscitation, and hourly monitoring are often required alongside antibiotics. The Hour-1 Bundle saves lives that antibiotics alone cannot.',
        'source'    => 'Surviving Sepsis Campaign 2021 International Guidelines'
    ],
    [
        'statement' => 'If a patient is in a coma, family visits only disturb the ICU and add no value.',
        'fact'      => 'Family visits to ICU patients demonstrably improve outcomes. The PICS-F (Post-Intensive Care Syndrome – Family) model shows that informed, supported families help reduce patient anxiety, delirium, and even length of ICU stay. We encourage structured family communication daily.',
        'source'    => 'SCCM Family-Centred Care Task Force / PICS Literature Review 2022'
    ],
    [
        'statement' => 'Once a patient goes on dialysis in the ICU, their kidneys will never recover.',
        'fact'      => 'ICU-related Acute Kidney Injury (AKI) is often reversible. CRRT (Continuous Renal Replacement Therapy) gives haemodynamically unstable kidneys time to rest and recover. With proper management, many patients regain independent kidney function and are discharged dialysis-free.',
        'source'    => 'KDIGO AKI Clinical Practice Guideline 2022 Update'
    ],
    [
        'statement' => 'Patients who survive the ICU are back to normal within a few weeks.',
        'fact'      => 'Post-Intensive Care Syndrome (PICS) affects 30–50% of ICU survivors — causing cognitive impairment, muscle weakness, anxiety, and PTSD that can last years. Early physiotherapy, ICU diaries, and structured follow-up are critical to long-term recovery.',
        'source'    => 'SCCM PICS Task Force / Needham et al., JAMA 2012'
    ],
    [
        'statement' => 'Prone positioning (face-down) in the ICU is too dangerous to be used routinely.',
        'fact'      => 'Prone positioning for 16+ hours/day in severe ARDS reduces ICU mortality by 16% (PROSEVA Trial). It is now the international standard of care for moderate-severe ARDS. At Apollo, we implement proning under structured team protocols for all qualifying patients.',
        'source'    => 'PROSEVA Trial — New England Journal of Medicine (2013)'
    ],
    [
        'statement' => 'The longer a patient stays in the ICU, the more they recover.',
        'fact'      => 'Prolonged ICU stays significantly increase the risk of ICU-acquired infections, delirium, muscle wasting, and psychological harm. Every day on a ventilator adds risk. The goal is always safe, rapid de-escalation — from ventilator, to weaning, to ward, to home.',
        'source'    => 'Clinical Pulmonary Medicine / SCCM ICU Liberation Guidelines'
    ],
];

if (insertIfEmpty($pdo, 'quiz', 'myth_busters', $myths)) {
    $results[] = '✅ Myth Busters — ' . count($myths) . ' cards seeded.';
} else {
    $results[] = '⏭️  Myth Busters — already has data, skipped.';
}

// ══════════════════════════════════════════════════════════════════
// 2. QUIZ QUESTIONS
// ══════════════════════════════════════════════════════════════════
$quiz = [
    [
        'q'           => 'According to Sepsis-3 (2016), sepsis is defined as:',
        'options'     => [
            'A severe infection requiring IV antibiotics',
            'Life-threatening organ dysfunction caused by a dysregulated host response to infection',
            'A fever above 38.5°C with a suspected infection source',
            'Systemic Inflammatory Response Syndrome (SIRS) plus a positive blood culture'
        ],
        'correct'     => 1,
        'explanation' => 'Sepsis-3 redefined sepsis as life-threatening organ dysfunction caused by a dysregulated host response to infection — identified by a SOFA score increase of ≥2 points. The old SIRS-based definition was replaced as it was too non-specific.'
    ],
    [
        'q'           => 'ECMO stands for:',
        'options'     => [
            'External Cardiac Monitor Output',
            'Extracorporeal Membrane Oxygenation',
            'Emergency Cardiopulmonary Management Option',
            'Extravascular Circulatory Membrane Oxygenator'
        ],
        'correct'     => 1,
        'explanation' => 'ECMO — Extracorporeal Membrane Oxygenation — temporarily takes over the function of the heart and/or lungs by circulating blood outside the body, oxygenating it, removing CO₂, and returning it. It is used in the most severe cardiac and respiratory failure cases.'
    ],
    [
        'q'           => 'What is the first-line vasopressor for septic shock according to the Surviving Sepsis Campaign 2021?',
        'options'     => [
            'Dopamine',
            'Epinephrine (Adrenaline)',
            'Norepinephrine (Noradrenaline)',
            'Vasopressin'
        ],
        'correct'     => 2,
        'explanation' => 'Norepinephrine is the recommended first-line vasopressor for septic shock per SSC 2021. It increases systemic vascular resistance without the tachycardic effects of dopamine. Vasopressin is added as a second agent if norepinephrine alone is insufficient.'
    ],
    [
        'q'           => 'The lung-protective tidal volume target for mechanically ventilated ARDS patients (per the ARMA trial) is:',
        'options'     => [
            '12 mL/kg ideal body weight',
            '8 mL/kg ideal body weight',
            '6 mL/kg ideal body weight',
            '4 mL/kg ideal body weight'
        ],
        'correct'     => 2,
        'explanation' => 'The landmark ARMA trial (ARDSNet 2000) demonstrated that limiting tidal volumes to 6 mL/kg ideal body weight reduced 28-day ARDS mortality by 22% versus 12 mL/kg. This is now the gold standard for lung-protective ventilation worldwide.'
    ],
    [
        'q'           => 'The qSOFA score (used for rapid bedside sepsis screening) consists of:',
        'options'     => [
            'Temperature, Heart Rate, White Cell Count',
            'Altered mental status, Respiratory Rate ≥22, Systolic BP ≤100 mmHg',
            'Lactate, Urine output, Blood pressure',
            'GCS, SpO₂, Respiratory Rate'
        ],
        'correct'     => 1,
        'explanation' => 'qSOFA (quick SOFA) uses three bedside criteria requiring no lab tests: Altered mental status (GCS<15), Respiratory Rate ≥22 breaths/min, and Systolic Blood Pressure ≤100 mmHg. A score of ≥2 identifies patients at risk of sepsis-related complications.'
    ],
    [
        'q'           => 'CRRT in the ICU stands for:',
        'options'     => [
            'Controlled Resuscitation and Replacement Therapy',
            'Continuous Renal Replacement Therapy',
            'Cardiac Recovery and Resuscitation Technique',
            'Critical Respiratory Rate Tracking'
        ],
        'correct'     => 1,
        'explanation' => 'CRRT — Continuous Renal Replacement Therapy — is a 24/7 form of dialysis used for haemodynamically unstable ICU patients with acute kidney injury. Unlike intermittent haemodialysis, CRRT applies gentler fluid and solute removal, protecting unstable patients from dangerous blood pressure fluctuations.'
    ],
    [
        'q'           => 'The PROSEVA trial (2013) demonstrated that prone positioning in moderate-severe ARDS:',
        'options'     => [
            'Made no difference to mortality',
            'Increased complication rates unacceptably',
            'Reduced 28-day mortality by approximately 16%',
            'Was only beneficial in VV-ECMO patients'
        ],
        'correct'     => 2,
        'explanation' => 'The PROSEVA trial showed that prone positioning for ≥16 hours/day in patients with PaO₂/FiO₂ <150 mmHg reduced 28-day mortality from 32.8% to 16% — a 16% absolute reduction. Proning is now standard of care for moderate-severe ARDS.'
    ],
    [
        'q'           => 'Approximately what percentage of ICU patients experience delirium during their stay?',
        'options'     => [
            'Around 10%',
            'Around 25%',
            'Around 50%',
            'Around 80%'
        ],
        'correct'     => 2,
        'explanation' => 'ICU delirium affects approximately 50% of all ICU patients and up to 80% of mechanically ventilated patients. It is associated with longer ICU stays, increased mortality, and long-term cognitive impairment (PICS). The ABCDEF bundle — including daily awakening trials and early mobilisation — significantly reduces delirium incidence.'
    ],
    [
        'q'           => 'In the Surviving Sepsis Campaign Hour-1 Bundle for Septic Shock, which of the following is included?',
        'options'     => [
            'Measure lactate and re-measure if initial lactate is >2 mmol/L',
            'Start broad-spectrum antibiotics within 6 hours',
            'Administer IV fluids only after vasopressor initiation',
            'Perform CT scan before blood culture collection'
        ],
        'correct'     => 0,
        'explanation' => 'The SSC Hour-1 Bundle includes: measuring lactate (re-measure if >2 mmol/L), obtaining blood cultures before antibiotics, administering broad-spectrum antibiotics, giving 30 mL/kg IV crystalloid for hypotension or lactate ≥4, and applying vasopressors if needed. Every step targets the first hour of recognition.'
    ],
    [
        'q'           => 'Post-Intensive Care Syndrome (PICS) refers to:',
        'options'     => [
            'Infections acquired in the ICU during mechanical ventilation',
            'New or worsening impairments in physical, cognitive, or mental health persisting after ICU discharge',
            'The inflammation syndrome that triggers ARDS in sepsis patients',
            'A complication specific to ECMO circuit management'
        ],
        'correct'     => 1,
        'explanation' => 'PICS (Post-Intensive Care Syndrome) describes new or worsening problems in physical function (weakness, fatigue), cognition (memory, attention), and mental health (anxiety, PTSD, depression) that emerge after critical illness and persist after hospital discharge. It affects 30–50% of ICU survivors and can last for years.'
    ],
];

if (insertIfEmpty($pdo, 'quiz', 'quiz_questions', $quiz)) {
    $results[] = '✅ Quiz Questions — ' . count($quiz) . ' questions seeded.';
} else {
    $results[] = '⏭️  Quiz Questions — already has data, skipped.';
}

// ══════════════════════════════════════════════════════════════════
// 3. DIYAS
// ══════════════════════════════════════════════════════════════════
$diyas = [
    [
        'id'       => 'diya_' . bin2hex(random_bytes(8)),
        'name'     => 'Papa — Rajesh Mehta',
        'prayer'   => 'You fought so hard. This diya burns for every breath you took on that ventilator, and every breath you take freely now. We will never forget what the ICU team did for our family.',
        'lit_by'   => 'Priya Mehta',
        'lit_at'   => '2026-04-20 09:15:00',
        'status'   => 'approved',
        'ip_hash'  => hash('sha256', '127.0.0.1' . '2026-04'),
    ],
    [
        'id'       => 'diya_' . bin2hex(random_bytes(8)),
        'name'     => 'Maa — Savitaben Patel',
        'prayer'   => 'She came home after 22 days in the ICU. The doctors said it would take a miracle. This diya is for all the miracles that modern medicine makes possible, and for the team that never gave up.',
        'lit_by'   => 'Kiran Patel',
        'lit_at'   => '2026-04-19 18:30:00',
        'status'   => 'approved',
        'ip_hash'  => hash('sha256', '127.0.0.2' . '2026-04'),
    ],
    [
        'id'       => 'diya_' . bin2hex(random_bytes(8)),
        'name'     => 'My husband — Arvind Shah',
        'prayer'   => 'ECMO. Three weeks. Six doctors. One result — he walked out of Apollo. I light this diya for Dr. Kothari\'s team, for the nurses who held my hand, and for my husband who never stopped fighting.',
        'lit_by'   => 'Meena Shah',
        'lit_at'   => '2026-04-18 12:00:00',
        'status'   => 'approved',
        'ip_hash'  => hash('sha256', '127.0.0.3' . '2026-04'),
    ],
    [
        'id'       => 'diya_' . bin2hex(random_bytes(8)),
        'name'     => 'Dada — Chandrakant Desai',
        'prayer'   => 'He did not make it home. But he was never alone, even in the ICU. Dr. Kothari sat with us at 2am and explained everything. This diya is for him — and for all the families who walked the same corridor we did.',
        'lit_by'   => 'Rohit Desai',
        'lit_at'   => '2026-04-17 20:45:00',
        'status'   => 'approved',
        'ip_hash'  => hash('sha256', '127.0.0.4' . '2026-04'),
    ],
    [
        'id'       => 'diya_' . bin2hex(random_bytes(8)),
        'name'     => 'Baby Ananya',
        'prayer'   => 'Our daughter was born too early. She spent 47 days in the NICU and ICU. She is 3 years old now and runs everywhere. We light this diya every year on her birthday.',
        'lit_by'   => 'Deepa & Suresh Naik',
        'lit_at'   => '2026-04-15 11:00:00',
        'status'   => 'approved',
        'ip_hash'  => hash('sha256', '127.0.0.5' . '2026-04'),
    ],
    [
        'id'       => 'diya_' . bin2hex(random_bytes(8)),
        'name'     => 'Nanu — Jayantilal Raval',
        'prayer'   => 'Sepsis came without warning. One day he was fine, the next he was in the ICU fighting for every breath. Fourteen days later he walked out. A diya for the team that made this possible.',
        'lit_by'   => 'Bhavin Raval',
        'lit_at'   => '2026-04-14 16:20:00',
        'status'   => 'approved',
        'ip_hash'  => hash('sha256', '127.0.0.6' . '2026-04'),
    ],
    [
        'id'       => 'diya_' . bin2hex(random_bytes(8)),
        'name'     => 'My father — Kamlesh Trivedi',
        'prayer'   => 'He was on CRRT for 11 days. His kidneys recovered. His spirit never wavered. This diya burns for everyone whose family is sitting in that ICU waiting room right now — there is hope.',
        'lit_by'   => 'Aarti Trivedi',
        'lit_at'   => '2026-04-12 08:30:00',
        'status'   => 'approved',
        'ip_hash'  => hash('sha256', '127.0.0.7' . '2026-04'),
    ],
    [
        'id'       => 'diya_' . bin2hex(random_bytes(8)),
        'name'     => 'All ICU families tonight',
        'prayer'   => 'I don\'t know the names of the families sitting in the Apollo ICU waiting room tonight. But I know exactly what they feel. We were there two years ago. This diya is for them. It gets better.',
        'lit_by'   => 'Anonymous — Ahmedabad',
        'lit_at'   => '2026-04-10 22:00:00',
        'status'   => 'approved',
        'ip_hash'  => hash('sha256', '127.0.0.8' . '2026-04'),
    ],
    [
        'id'       => 'diya_' . bin2hex(random_bytes(8)),
        'name'     => 'Dr. Kothari\'s entire ICU team',
        'prayer'   => 'They gave up their festivals, their nights, their weekends. They gave my mother back to us. A diya for every doctor, nurse, and technician who makes Apollo\'s ICU what it is.',
        'lit_by'   => 'Harshad Bhatt',
        'lit_at'   => '2026-04-08 19:15:00',
        'status'   => 'approved',
        'ip_hash'  => hash('sha256', '127.0.0.9' . '2026-04'),
    ],
    [
        'id'       => 'diya_' . bin2hex(random_bytes(8)),
        'name'     => 'Uncle — Mahendra Solanki',
        'prayer'   => 'Three organ systems failing. PaO₂ at 50. The doctor said they would try ECMO. We didn\'t know what it meant. We know now — it means a second chance. This diya is that second chance made visible.',
        'lit_by'   => 'Tejas Solanki',
        'lit_at'   => '2026-04-05 14:00:00',
        'status'   => 'approved',
        'ip_hash'  => hash('sha256', '127.0.0.10' . '2026-04'),
    ],
];

// Append to existing diyas (2 test entries already exist — we add our realistic ones)
$total = appendItems($pdo, 'community', 'diyas', $diyas);
$results[] = '✅ Diyas — ' . count($diyas) . ' new diyas added (' . $total . ' total now).';

// ══════════════════════════════════════════════════════════════════
// 4. HEALING STORIES
// ══════════════════════════════════════════════════════════════════
$stories = [
    [
        'id'           => 'healing_stories_' . bin2hex(random_bytes(8)),
        'status'       => 'approved',
        'submitted_at' => '2026-03-15 10:00:00',
        'patient_name' => 'Rajesh Mehta',
        'family_name'  => 'Priya Mehta',
        'relationship' => 'Daughter',
        'duration'     => '18 days in ICU',
        'tag'          => 'Sepsis Recovery',
        'title'        => 'He walked out. We never thought he would.',
        'story'        => 'My father was 62 when sepsis took him from fine to ICU in less than 24 hours. We rushed to Apollo at 2am. By morning he was on a ventilator. For 18 days, Dr. Kothari\'s team updated us every single morning — sometimes good news, sometimes hard news, but always honest. When Papa finally walked out of the ICU on Day 18, his physio team cheering him, I cried for the first time in three weeks. This is what a world-class ICU looks like.',
        'quote'        => '"Always honest. Sometimes hard. That is what we needed."',
    ],
    [
        'id'           => 'healing_stories_' . bin2hex(random_bytes(8)),
        'status'       => 'approved',
        'submitted_at' => '2026-02-20 15:30:00',
        'patient_name' => 'Arvind Shah',
        'family_name'  => 'Meena Shah',
        'relationship' => 'Wife',
        'duration'     => '22 days, 18 on VV-ECMO',
        'tag'          => 'ECMO Survivor',
        'title'        => 'The machine that breathed for him.',
        'story'        => 'My husband developed severe ARDS after a viral pneumonia. His lungs were at 12% function. Dr. Kothari explained ECMO to us in Gujarati at midnight — patiently, without rushing, drawing a diagram on paper. He said the machine would breathe for Arvind while his lungs rested. Eighteen days later, they began weaning him off. Every day was a battle. But Dr. Kothari was there — not just during rounds, but answering my calls personally when I was scared at 3am. Arvind is home. He goes for walks every morning.',
        'quote'        => '"He said the machine would breathe for him. It did. And then Arvind breathed for himself again."',
    ],
    [
        'id'           => 'healing_stories_' . bin2hex(random_bytes(8)),
        'status'       => 'approved',
        'submitted_at' => '2026-01-10 09:00:00',
        'patient_name' => 'Savitaben Patel',
        'family_name'  => 'Kiran Patel',
        'relationship' => 'Son',
        'duration'     => '11 days in ICU',
        'tag'          => 'Multi-Organ Recovery',
        'title'        => 'Three systems failed. She recovered all three.',
        'story'        => 'Maa had kidney failure, lung failure, and her blood pressure crashed — all within 48 hours of admission. We were told it was very serious. CRRT was started for her kidneys. A ventilator for her lungs. And norepinephrine for her blood pressure. It sounded impossible. But Dr. Kothari sat with my brother and me every evening and walked us through each number. By Day 7, the CRRT was stopped. By Day 9, she was breathing on her own. By Day 11, she was asking for chai. She is 71 and she is home.',
        'quote'        => '"By day 11 she was asking for chai. We knew she was back."',
    ],
    [
        'id'           => 'healing_stories_' . bin2hex(random_bytes(8)),
        'status'       => 'approved',
        'submitted_at' => '2025-12-05 18:00:00',
        'patient_name' => 'Ramesh Joshi',
        'family_name'  => 'Anita Joshi',
        'relationship' => 'Wife',
        'duration'     => '14 days in ICU, 3 on CRRT',
        'tag'          => 'Cardiac & Renal Recovery',
        'title'        => 'Post-cardiac surgery — and then the kidneys went.',
        'story'        => 'Ramesh had bypass surgery at another hospital. Complications brought him to Apollo\'s ICU with AKI and haemodynamic instability. I was terrified — we had just been through a major surgery and now this. Dr. Kothari explained every step: why CRRT was needed, how long it would likely run, what signs would tell them it was working. His calmness became my calmness. Ramesh\'s kidneys recovered. He required no dialysis at discharge. We are deeply grateful.',
        'quote'        => '"His calmness became my calmness."',
    ],
];

if (insertIfEmpty($pdo, 'community', 'healing_stories', $stories)) {
    $results[] = '✅ Healing Stories — ' . count($stories) . ' stories seeded.';
} else {
    $results[] = '⏭️  Healing Stories — already has data, skipped.';
}

// ══════════════════════════════════════════════════════════════════
// 5. GRATITUDE NOTES
// ══════════════════════════════════════════════════════════════════
$notes = [
    [
        'id'           => 'gratitude_notes_' . bin2hex(random_bytes(8)),
        'status'       => 'approved',
        'submitted_at' => '2026-04-01 10:00:00',
        'name'         => 'Bhavin & Kavita Raval',
        'relationship' => 'Family of patient',
        'note'         => 'Thank you for explaining everything in Gujarati. It sounds simple, but for our family, it was everything. We understood what was happening to our father every single day. That clarity gave us peace in the hardest week of our lives.',
    ],
    [
        'id'           => 'gratitude_notes_' . bin2hex(random_bytes(8)),
        'status'       => 'approved',
        'submitted_at' => '2026-03-22 14:00:00',
        'name'         => 'Dr. Priyanka Nair',
        'relationship' => 'Colleague, Pulmonologist',
        'note'         => 'I have referred three of my most complex respiratory failure patients to Dr. Kothari over the past two years. Each time, the family feedback has been exceptional. The clinical outcomes speak for themselves, but it is the communication and compassion that set this team apart.',
    ],
    [
        'id'           => 'gratitude_notes_' . bin2hex(random_bytes(8)),
        'status'       => 'approved',
        'submitted_at' => '2026-02-14 09:30:00',
        'name'         => 'Tejas Solanki',
        'relationship' => 'Nephew of patient',
        'note'         => 'My uncle was on ECMO for 15 days. Dr. Kothari called me personally on Day 3 to explain a change in the plan. No hospital has ever done that for our family. He treats families as partners, not bystanders. That means more than I can express.',
    ],
    [
        'id'           => 'gratitude_notes_' . bin2hex(random_bytes(8)),
        'status'       => 'approved',
        'submitted_at' => '2026-01-28 16:00:00',
        'name'         => 'Harshad & Sonal Bhatt',
        'relationship' => 'Family of patient',
        'note'         => 'When my mother was discharged after 19 ICU days, the entire nursing team stood at the door. One nurse held Maa\'s hand and said, "We were rooting for you every day." I will never forget that moment. This team has a soul.',
    ],
    [
        'id'           => 'gratitude_notes_' . bin2hex(random_bytes(8)),
        'status'       => 'approved',
        'submitted_at' => '2025-12-20 11:00:00',
        'name'         => 'Aarti Trivedi',
        'relationship' => 'Daughter of patient',
        'note'         => 'Papa\'s kidneys failed post-surgery. CRRT for 11 days. Dr. Kothari explained it would be a slow process and not to look for day-to-day progress. That one piece of advice stopped us from panicking every morning. On Day 11, CRRT stopped. Papa\'s kidneys recovered. I will hold that moment forever.',
    ],
];

if (insertIfEmpty($pdo, 'community', 'gratitude_notes', $notes)) {
    $results[] = '✅ Gratitude Notes — ' . count($notes) . ' notes seeded.';
} else {
    $results[] = '⏭️  Gratitude Notes — already has data, skipped.';
}

// ══════════════════════════════════════════════════════════════════
// 6. DIYA QUOTES (shown on the diya page)
// ══════════════════════════════════════════════════════════════════
$diyaQuotes = [
    ['id' => 'quote_' . bin2hex(random_bytes(8)), 'text' => 'Even in the darkest ICU night, a single diya reminds us that light always finds a way.', 'author' => 'Dr. Jay Kothari', 'status' => 'active'],
    ['id' => 'quote_' . bin2hex(random_bytes(8)), 'text' => 'Every patient is someone\'s entire world. We treat them that way.', 'author' => 'Dr. Jay Kothari', 'status' => 'active'],
    ['id' => 'quote_' . bin2hex(random_bytes(8)), 'text' => 'Hope is not wishful thinking. In critical care, hope is a protocol — we follow it one hour at a time.', 'author' => 'Dr. Jay Kothari', 'status' => 'active'],
    ['id' => 'quote_' . bin2hex(random_bytes(8)), 'text' => 'The family waiting outside the ICU is part of the treatment. Never underestimate the healing power of a loved one\'s voice.', 'author' => 'Critical Care Medicine Principle', 'status' => 'active'],
    ['id' => 'quote_' . bin2hex(random_bytes(8)), 'text' => 'Light a diya. Say a prayer. Then trust the science. Both belong in the ICU.', 'author' => 'Dr. Jay Kothari', 'status' => 'active'],
];

if (insertIfEmpty($pdo, 'community', 'diya_quotes', $diyaQuotes)) {
    $results[] = '✅ Diya Quotes — ' . count($diyaQuotes) . ' quotes seeded.';
} else {
    $results[] = '⏭️  Diya Quotes — already has data, skipped.';
}

// ═══════════════════════════════════════════
// OUTPUT
// ═══════════════════════════════════════════
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Seed Results</title>
<style>body{font-family:system-ui,sans-serif;max-width:700px;margin:60px auto;padding:20px;background:#f0f2f8;}
h1{color:#0a1628;}ul{list-style:none;padding:0;}li{padding:12px 16px;margin:8px 0;background:#fff;border-radius:8px;border-left:4px solid #00d4aa;font-size:1rem;}
.warn{background:#fff3cd;border-left-color:#f59e0b;color:#92400e;padding:16px;border-radius:8px;margin-top:24px;}</style></head><body>';
echo '<h1>🌱 Seed Complete</h1><ul>';
foreach ($results as $r) echo "<li>$r</li>";
echo '</ul>';
echo '<div class="warn">⚠️ <strong>Important:</strong> Delete or rename <code>api/seed_data.php</code> now that seeding is complete. It must not remain accessible in production.</div>';
echo '</body></html>';
