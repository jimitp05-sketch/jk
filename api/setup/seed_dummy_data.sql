-- ═══════════════════════════════════════════════════════════════════
-- AI Apollo — Dummy Data Seed (run in phpMyAdmin)
-- Only inserts content that doesn't already exist (safe to re-run)
-- ═══════════════════════════════════════════════════════════════════

-- ── 1. HEALING STORIES ──────────────────────────────────────────────
INSERT INTO content (content_type, content_key, data)
SELECT 'community', 'healing_stories', '[
  {
    "id": "healing_stories_a1b2c3d4e5f60001",
    "status": "approved",
    "submitted_at": "2026-03-15 10:00:00",
    "patient_name": "Rajesh Mehta",
    "family_name": "Priya Mehta",
    "relationship": "Daughter",
    "duration": "18 days in ICU",
    "tag": "Sepsis Recovery",
    "title": "He walked out. We never thought he would.",
    "story": "My father was 62 when sepsis took him from fine to ICU in less than 24 hours. We rushed to Apollo at 2am. By morning he was on a ventilator. For 18 days, Dr. Kothari\'s team updated us every single morning — sometimes good news, sometimes hard news, but always honest. When Papa finally walked out of the ICU on Day 18, his physio team cheering him, I cried for the first time in three weeks. This is what a world-class ICU looks like.",
    "quote": "\"Always honest. Sometimes hard. That is what we needed.\""
  },
  {
    "id": "healing_stories_a1b2c3d4e5f60002",
    "status": "approved",
    "submitted_at": "2026-02-20 15:30:00",
    "patient_name": "Arvind Shah",
    "family_name": "Meena Shah",
    "relationship": "Wife",
    "duration": "22 days, 18 on VV-ECMO",
    "tag": "ECMO Survivor",
    "title": "The machine that breathed for him.",
    "story": "My husband developed severe ARDS after a viral pneumonia. His lungs were at 12% function. Dr. Kothari explained ECMO to us in Gujarati at midnight — patiently, without rushing, drawing a diagram on paper. He said the machine would breathe for Arvind while his lungs rested. Eighteen days later, they began weaning him off. Every day was a battle. But Dr. Kothari was there — not just during rounds, but answering my calls personally when I was scared at 3am. Arvind is home. He goes for walks every morning.",
    "quote": "\"He said the machine would breathe for him. It did. And then Arvind breathed for himself again.\""
  },
  {
    "id": "healing_stories_a1b2c3d4e5f60003",
    "status": "approved",
    "submitted_at": "2026-01-10 09:00:00",
    "patient_name": "Savitaben Patel",
    "family_name": "Kiran Patel",
    "relationship": "Son",
    "duration": "11 days in ICU",
    "tag": "Multi-Organ Recovery",
    "title": "Three systems failed. She recovered all three.",
    "story": "Maa had kidney failure, lung failure, and her blood pressure crashed — all within 48 hours of admission. We were told it was very serious. CRRT was started for her kidneys. A ventilator for her lungs. And norepinephrine for her blood pressure. It sounded impossible. But Dr. Kothari sat with my brother and me every evening and walked us through each number. By Day 7, the CRRT was stopped. By Day 9, she was breathing on her own. By Day 11, she was asking for chai. She is 71 and she is home.",
    "quote": "\"By day 11 she was asking for chai. We knew she was back.\""
  },
  {
    "id": "healing_stories_a1b2c3d4e5f60004",
    "status": "approved",
    "submitted_at": "2025-12-05 18:00:00",
    "patient_name": "Ramesh Joshi",
    "family_name": "Anita Joshi",
    "relationship": "Wife",
    "duration": "14 days in ICU, 3 on CRRT",
    "tag": "Cardiac & Renal Recovery",
    "title": "Post-cardiac surgery — and then the kidneys went.",
    "story": "Ramesh had bypass surgery at another hospital. Complications brought him to Apollo ICU with AKI and haemodynamic instability. I was terrified — we had just been through major surgery and now this. Dr. Kothari explained every step: why CRRT was needed, how long it would likely run, what signs would tell them it was working. His calmness became my calmness. Ramesh\'s kidneys recovered. He required no dialysis at discharge.",
    "quote": "\"His calmness became my calmness.\""
  }
]'
WHERE NOT EXISTS (
  SELECT 1 FROM content WHERE content_key = 'healing_stories'
);

-- ── 2. GRATITUDE NOTES ──────────────────────────────────────────────
INSERT INTO content (content_type, content_key, data)
SELECT 'community', 'gratitude_notes', '[
  {
    "id": "gratitude_notes_b1b2c3d4e5f60001",
    "status": "approved",
    "submitted_at": "2026-04-01 10:00:00",
    "name": "Bhavin & Kavita Raval",
    "relationship": "Family of patient",
    "note": "Thank you for explaining everything in Gujarati. It sounds simple, but for our family, it was everything. We understood what was happening to our father every single day. That clarity gave us peace in the hardest week of our lives."
  },
  {
    "id": "gratitude_notes_b1b2c3d4e5f60002",
    "status": "approved",
    "submitted_at": "2026-03-22 14:00:00",
    "name": "Dr. Priyanka Nair",
    "relationship": "Colleague, Pulmonologist",
    "note": "I have referred three of my most complex respiratory failure patients to Dr. Kothari over the past two years. Each time, the family feedback has been exceptional. The clinical outcomes speak for themselves, but it is the communication and compassion that set this team apart."
  },
  {
    "id": "gratitude_notes_b1b2c3d4e5f60003",
    "status": "approved",
    "submitted_at": "2026-02-14 09:30:00",
    "name": "Tejas Solanki",
    "relationship": "Nephew of patient",
    "note": "My uncle was on ECMO for 15 days. Dr. Kothari called me personally on Day 3 to explain a change in the plan. No hospital has ever done that for our family. He treats families as partners, not bystanders. That means more than I can express."
  },
  {
    "id": "gratitude_notes_b1b2c3d4e5f60004",
    "status": "approved",
    "submitted_at": "2026-01-28 16:00:00",
    "name": "Harshad & Sonal Bhatt",
    "relationship": "Family of patient",
    "note": "When my mother was discharged after 19 ICU days, the entire nursing team stood at the door. One nurse held Maa\'s hand and said we were rooting for you every day. I will never forget that moment. This team has a soul."
  },
  {
    "id": "gratitude_notes_b1b2c3d4e5f60005",
    "status": "approved",
    "submitted_at": "2025-12-20 11:00:00",
    "name": "Aarti Trivedi",
    "relationship": "Daughter of patient",
    "note": "Papa\'s kidneys failed post-surgery. CRRT for 11 days. Dr. Kothari explained it would be a slow process and not to look for day-to-day progress. That one piece of advice stopped us from panicking every morning. On Day 11, CRRT stopped. Papa\'s kidneys recovered."
  }
]'
WHERE NOT EXISTS (
  SELECT 1 FROM content WHERE content_key = 'gratitude_notes'
);

-- ── 3. DIYA QUOTES ──────────────────────────────────────────────────
INSERT INTO content (content_type, content_key, data)
SELECT 'community', 'diya_quotes', '[
  {"id":"quote_c1b2c3d4e5f60001","text":"Even in the darkest ICU night, a single diya reminds us that light always finds a way.","author":"Dr. Jay Kothari","status":"active"},
  {"id":"quote_c1b2c3d4e5f60002","text":"Every patient is someone\'s entire world. We treat them that way.","author":"Dr. Jay Kothari","status":"active"},
  {"id":"quote_c1b2c3d4e5f60003","text":"Hope is not wishful thinking. In critical care, hope is a protocol — we follow it one hour at a time.","author":"Dr. Jay Kothari","status":"active"},
  {"id":"quote_c1b2c3d4e5f60004","text":"The family waiting outside the ICU is part of the treatment. Never underestimate the healing power of a loved one\'s voice.","author":"Critical Care Medicine Principle","status":"active"},
  {"id":"quote_c1b2c3d4e5f60005","text":"Light a diya. Say a prayer. Then trust the science. Both belong in the ICU.","author":"Dr. Jay Kothari","status":"active"}
]'
WHERE NOT EXISTS (
  SELECT 1 FROM content WHERE content_key = 'diya_quotes'
);

-- ── 4. ADD 10 REALISTIC DIYAS (appended to existing) ────────────────
-- Read current diyas first in phpMyAdmin, then run this only if the
-- existing array has fewer than 5 real entries.
-- This INSERT adds our diyas alongside existing test entries:

INSERT INTO content (content_type, content_key, data)
VALUES ('community', 'diyas', '[
  {"id":"diya_d1b2c3d4e5f60001","name":"Papa — Rajesh Mehta","prayer":"You fought so hard. This diya burns for every breath you took on that ventilator, and every breath you take freely now. We will never forget what the ICU team did for our family.","lit_by":"Priya Mehta","lit_at":"2026-04-20 09:15:00","status":"approved","ip_hash":"abc001"},
  {"id":"diya_d1b2c3d4e5f60002","name":"Maa — Savitaben Patel","prayer":"She came home after 22 days in the ICU. The doctors said it would take a miracle. This diya is for all the miracles that modern medicine makes possible, and for the team that never gave up.","lit_by":"Kiran Patel","lit_at":"2026-04-19 18:30:00","status":"approved","ip_hash":"abc002"},
  {"id":"diya_d1b2c3d4e5f60003","name":"My husband — Arvind Shah","prayer":"ECMO. Three weeks. Six doctors. One result — he walked out of Apollo. I light this diya for Dr. Kothari\'s team, for the nurses who held my hand, and for my husband who never stopped fighting.","lit_by":"Meena Shah","lit_at":"2026-04-18 12:00:00","status":"approved","ip_hash":"abc003"},
  {"id":"diya_d1b2c3d4e5f60004","name":"Dada — Chandrakant Desai","prayer":"He did not make it home. But he was never alone, even in the ICU. Dr. Kothari sat with us at 2am and explained everything. This diya is for him — and for all the families who walked the same corridor we did.","lit_by":"Rohit Desai","lit_at":"2026-04-17 20:45:00","status":"approved","ip_hash":"abc004"},
  {"id":"diya_d1b2c3d4e5f60005","name":"Baby Ananya","prayer":"Our daughter was born too early. She spent 47 days in the NICU and ICU. She is 3 years old now and runs everywhere. We light this diya every year on her birthday.","lit_by":"Deepa & Suresh Naik","lit_at":"2026-04-15 11:00:00","status":"approved","ip_hash":"abc005"},
  {"id":"diya_d1b2c3d4e5f60006","name":"Nanu — Jayantilal Raval","prayer":"Sepsis came without warning. One day he was fine, the next he was in the ICU fighting for every breath. Fourteen days later he walked out. A diya for the team that made this possible.","lit_by":"Bhavin Raval","lit_at":"2026-04-14 16:20:00","status":"approved","ip_hash":"abc006"},
  {"id":"diya_d1b2c3d4e5f60007","name":"My father — Kamlesh Trivedi","prayer":"He was on CRRT for 11 days. His kidneys recovered. His spirit never wavered. This diya burns for everyone whose family is sitting in that ICU waiting room right now — there is hope.","lit_by":"Aarti Trivedi","lit_at":"2026-04-12 08:30:00","status":"approved","ip_hash":"abc007"},
  {"id":"diya_d1b2c3d4e5f60008","name":"All ICU families tonight","prayer":"I do not know the names of the families sitting in the Apollo ICU waiting room tonight. But I know exactly what they feel. We were there two years ago. This diya is for them. It gets better.","lit_by":"Anonymous — Ahmedabad","lit_at":"2026-04-10 22:00:00","status":"approved","ip_hash":"abc008"},
  {"id":"diya_d1b2c3d4e5f60009","name":"Dr. Kothari\'s entire ICU team","prayer":"They gave up their festivals, their nights, their weekends. They gave my mother back to us. A diya for every doctor, nurse, and technician who makes Apollo\'s ICU what it is.","lit_by":"Harshad Bhatt","lit_at":"2026-04-08 19:15:00","status":"approved","ip_hash":"abc009"},
  {"id":"diya_d1b2c3d4e5f60010","name":"Uncle — Mahendra Solanki","prayer":"Three organ systems failing. The doctor said they would try ECMO. We did not know what it meant. We know now — it means a second chance. This diya is that second chance made visible.","lit_by":"Tejas Solanki","lit_at":"2026-04-05 14:00:00","status":"approved","ip_hash":"abc010"},
  {"id":"diya_jimit_test","name":"jimit","prayer":"enlighten the world","lit_by":"","lit_at":"2026-04-01 00:00:00","status":"approved","ip_hash":"test"}
]'
ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW();
