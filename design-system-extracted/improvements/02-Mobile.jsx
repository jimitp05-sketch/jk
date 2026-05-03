// IMPROVEMENT 2 — MOBILE: tap-to-call, sticky FAB, persistent reach
const phoneFrame = (children) => (
  <div className="ba-phone">
    <div className="ba-phone-notch"/>
    <div className="ba-phone-screen">{children}</div>
  </div>
);

const MobileBefore = () => phoneFrame(
  <div className="ba-mob ba-mob-before">
    <div className="ba-mob-nav">
      <span style={{ color: '#fff', fontWeight: 700, fontSize: 12 }}>Dr. Jay <span className="jk-glow-text">Kothari</span></span>
      <div className="ba-burger"><span/><span/><span/></div>
    </div>
    <div className="ba-mob-hero">
      <p className="ba-mob-emp">"We know you're terrified right now."</p>
      <h2>Your Family Deserves <span className="jk-glow-text">The Best</span> ICU Doctor in Gujarat.</h2>
      <div className="ba-mob-creds">
        {['MBBS','MD','FNB','ECMO'].map(c => <span key={c}>{c}</span>)}
      </div>
      <div className="ba-mob-stats">
        <div><b className="jk-glow-text">30+</b><span>Years</span></div>
        <div><b className="jk-glow-text">10K+</b><span>Lives</span></div>
        <div><b className="jk-glow-text">&lt;10</b><span>ECMO</span></div>
      </div>
      <a className="ba-mob-cta-blue">Book OPD →</a>
    </div>
    {/* No phone number visible. To reach Dr. K user must scroll ~10 sections. */}
    <div className="ba-mob-section-stub">
      <div className="ba-stub-h">About Dr. Kothari</div>
      <div className="ba-stub-line"/><div className="ba-stub-line"/><div className="ba-stub-line short"/>
    </div>
    <div className="ba-mob-section-stub">
      <div className="ba-stub-h">Clinical Expertise</div>
      <div className="ba-stub-line"/><div className="ba-stub-line"/><div className="ba-stub-line short"/>
    </div>
    <div className="ba-mob-scroll-hint">↓ scroll 10 sections to find phone number</div>
  </div>
);

const MobileAfter = () => phoneFrame(
  <div className="ba-mob ba-mob-after">
    {/* Top emergency strip - 32px, persistent */}
    <div className="ba-mob-emerg">
      <span className="ba-emerg-pulse"/>Emergency? <a href="tel:18605001066">1860-500-1066</a>
    </div>
    <div className="ba-mob-nav">
      <span style={{ color: '#fff', fontWeight: 700, fontSize: 12 }}>Dr. Jay <span className="jk-glow-text">Kothari</span></span>
      {/* Tap-to-call button replaces decorative phone icon */}
      <a href="tel:18605001066" className="ba-mob-call-pill"><Icon name="phone" size={12}/> Call</a>
    </div>
    <div className="ba-mob-hero ba-mob-hero-after">
      <p className="ba-mob-emp">You're terrified. We know.<br/>Take a breath.</p>
      <h2>Call Dr. Kothari <span className="jk-glow-text">first.</span></h2>
      <a href="tel:18605001066" className="ba-mob-cta-call">
        <Icon name="phone" size={18}/>
        <div>
          <strong>Call Apollo ICU now</strong>
          <span>Hindi · Gujarati · English</span>
        </div>
      </a>
      <div className="ba-mob-cta-row">
        <a className="ba-mob-cta-wa"><Icon name="message" size={14}/> WhatsApp</a>
        <a className="ba-mob-cta-book"><Icon name="calendar" size={14}/> OPD</a>
      </div>
      <div className="ba-mob-microtrust">
        <Icon name="check" size={10}/> 30+ yrs · 10K+ lives · ECMO certified
      </div>
    </div>
    {/* FAB — floating WhatsApp button bottom right */}
    <div className="ba-mob-fab"><Icon name="message" size={22}/></div>
    <div className="ba-mob-scroll-hint ba-good">phone reachable from any scroll position</div>
  </div>
);

window.MobileBefore = MobileBefore;
window.MobileAfter = MobileAfter;
