// IMPROVEMENT 3 — BOOKING: friction-heavy form to multi-channel triage
const BookingBefore = () => (
  <div className="ba-book ba-book-before">
    <div className="ba-book-hero-before">
      <span className="jk-section-label">Schedule Your Visit</span>
      <h2>Book a <span className="jk-glow-text">Consultation</span></h2>
      <p>OPD consultations Mon–Sat, 2:00–4:00 PM</p>
    </div>
    <div className="ba-book-grid-before">
      <div className="ba-cal">
        <div className="ba-cal-head">
          <span>April 2026</span>
          <span className="ba-cal-arrows">‹ ›</span>
        </div>
        <div className="ba-cal-grid">
          {['M','T','W','T','F','S','S'].map((d,i) => <div key={i} className="ba-cal-dow">{d}</div>)}
          {Array.from({length: 30}).map((_,i) => (
            <div key={i} className={`ba-cal-day ${i===14?'sel':''} ${i%7===6?'off':''}`}>{i+1}</div>
          ))}
        </div>
      </div>
      <div className="ba-form-stub">
        <h4>📋 Your Details</h4>
        <div className="ba-form-row">
          <label>Patient Name *<input placeholder="Full name"/></label>
          <label>Phone *<input placeholder="+91"/></label>
        </div>
        <div className="ba-form-row">
          <label>Age <input/></label>
          <label>Gender <select><option/></select></label>
        </div>
        <label>Reason for visit *<textarea rows={2}/></label>
        <label>Past Medical History <textarea rows={2}/></label>
        <label>Current Medications <textarea rows={2}/></label>
        <label>Email <input/></label>
        <a className="ba-cta-blue ba-cta-block">Submit Request</a>
        <p className="ba-tiny">You'll be called back in 24–48 hours.</p>
      </div>
    </div>
  </div>
);

const BookingAfter = () => (
  <div className="ba-book ba-book-after">
    {/* Triage-first layout: pick urgency, then channel */}
    <div className="ba-book-tri">
      <div className="ba-book-tri-head">
        <p className="ba-tri-empathy">Tell us how urgent — we'll route you to the fastest path.</p>
        <h2 className="ba-tri-title">How can we help <span className="jk-glow-text">right now</span>?</h2>
      </div>
      <div className="ba-tri-cards">
        <a className="ba-tri-card ba-tri-emerg">
          <div className="ba-tri-icon ba-tri-icon-red"><Icon name="phone" size={24}/></div>
          <strong>Emergency · ICU now</strong>
          <p>Direct line to Dr. Kothari's team. Triage in &lt;5 min.</p>
          <span className="ba-tri-action">Call 1860-500-1066 →</span>
          <span className="ba-tri-eta">Answers in 30 sec</span>
        </a>
        <a className="ba-tri-card ba-tri-wa">
          <div className="ba-tri-icon ba-tri-icon-green"><Icon name="message" size={24}/></div>
          <strong>This week · second opinion</strong>
          <p>Send reports on WhatsApp. Dr. K reviews and replies same-day.</p>
          <span className="ba-tri-action">Open WhatsApp →</span>
          <span className="ba-tri-eta">Replies within 4 hours</span>
        </a>
        <a className="ba-tri-card ba-tri-opd">
          <div className="ba-tri-icon ba-tri-icon-teal"><Icon name="calendar" size={24}/></div>
          <strong>Standard OPD · Mon–Sat</strong>
          <p>Book a 2–4pm slot at Apollo Hospitals, Ahmedabad.</p>
          <span className="ba-tri-action">Pick a time →</span>
          <span className="ba-tri-eta">Next slot: Tue 2:30pm</span>
        </a>
      </div>
      {/* Just-enough form, only after they pick standard OPD */}
      <div className="ba-tri-mini">
        <div className="ba-tri-mini-row">
          <span className="ba-tri-step">1</span>
          <span>Picked: <strong>Tue, 2:30pm OPD</strong></span>
        </div>
        <div className="ba-tri-mini-row">
          <span className="ba-tri-step">2</span>
          <span>Just <strong>name + phone</strong> — bring reports to OPD. We'll handle the rest.</span>
        </div>
        <div className="ba-tri-form-row">
          <input placeholder="Your name"/>
          <input placeholder="+91 phone"/>
          <a className="ba-cta-emerald">Confirm slot</a>
        </div>
        <p className="ba-tri-foot"><Icon name="check" size={11}/> No medical history form. No email. <Icon name="check" size={11}/> SMS confirmation in 30 sec.</p>
      </div>
    </div>
  </div>
);

window.BookingBefore = BookingBefore;
window.BookingAfter = BookingAfter;
