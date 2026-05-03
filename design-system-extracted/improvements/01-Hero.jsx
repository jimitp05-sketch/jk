// IMPROVEMENT 1 — HERO: from designer-led to crisis-aware
const HeroBefore = () => (
  <div className="ba-hero ba-hero-before">
    <div className="ba-hero-orb"/>
    <div className="ba-hero-grid">
      <div>
        <span className="ba-hero-badge">Apollo Hospitals · Gujarat's #1 Critical Care Unit</span>
        <p className="ba-hero-empathy" style={{ opacity: .7 }}>"We know you're terrified right now. Take a breath. You've found Dr. Jay Kothari — and he's ready."</p>
        <h1 className="ba-hero-title">Your Family Deserves <span className="jk-glow-text">The Best</span> ICU Doctor in Gujarat.</h1>
        <div className="ba-cred-strip">
          {['MBBS','MD (Anaesthesia)','FNB Critical Care','ECMO Specialist'].map(c => <span key={c} className="ba-cred-pill">{c}</span>)}
        </div>
        <div className="ba-stats-row">
          <div><div className="ba-stat-num jk-glow-text">30+</div><div className="ba-stat-lbl">Years</div></div>
          <div><div className="ba-stat-num jk-glow-text">10,000+</div><div className="ba-stat-lbl">Lives</div></div>
          <div><div className="ba-stat-num jk-glow-text">&lt;10</div><div className="ba-stat-lbl">ECMO Docs</div></div>
        </div>
        <a href="#" className="ba-cta-blue">Book OPD → Mon–Sat, 2PM</a>
      </div>
      <div className="ba-hero-img-wrap">
        <img src="../assets/img-hero-doctor.webp" alt=""/>
      </div>
    </div>
  </div>
);

const HeroAfter = () => (
  <div className="ba-hero ba-hero-after">
    {/* Persistent emergency strip — top of page */}
    <div className="ba-emerg-strip">
      <span className="ba-emerg-pulse"/>
      <span><strong>ICU Emergency?</strong> Don't navigate. Call directly:</span>
      <a href="tel:18605001066" className="ba-emerg-num"><Icon name="phone" size={14}/> 1860-500-1066</a>
      <span className="ba-emerg-meta">24×7 · Apollo Triage</span>
    </div>
    <div className="ba-hero-orb"/>
    <div className="ba-hero-grid">
      <div>
        {/* Empathy moved ABOVE the headline — the lifeline lands first */}
        <p className="ba-hero-empathy ba-hero-empathy-promoted">
          You're terrified. We know.<br/>Take a breath — you've found him.
        </p>
        {/* Tighter, punchier headline (12 words) */}
        <h1 className="ba-hero-title-tight">
          When seconds define survival,<br/><span className="jk-glow-text">call Dr. Kothari first.</span>
        </h1>
        {/* Action stack: one for crisis, one for OPD, one for WhatsApp */}
        <div className="ba-action-stack">
          <a href="tel:18605001066" className="ba-cta-emergency">
            <Icon name="phone" size={18}/>
            <div><strong>Call Now · Speaks in your hour</strong><span>1860-500-1066 · Hindi · Gujarati · English</span></div>
          </a>
          <div className="ba-cta-row">
            <a href="#" className="ba-cta-wa"><Icon name="message" size={16}/> WhatsApp Triage</a>
            <a href="#" className="ba-cta-opd"><Icon name="calendar" size={16}/> Book OPD</a>
          </div>
        </div>
        {/* Stats demoted, credentials become micro-row */}
        <div className="ba-microtrust">
          <span><strong>30+ yrs</strong> · 10,000+ lives</span>
          <span className="ba-dot"/>
          <span><strong>&lt;10</strong> ECMO docs in Gujarat</span>
          <span className="ba-dot"/>
          <span>Apollo · BJ · Lilavati</span>
        </div>
        <div className="ba-cred-strip-mini">
          <Icon name="check" size={11}/> MBBS · MD · FNB Critical Care
        </div>
      </div>
      <div className="ba-hero-img-wrap">
        <img src="../assets/img-hero-doctor.webp" alt=""/>
        <div className="ba-hero-float ba-hero-float-top">
          <div className="ba-float-icon"><Icon name="heart-pulse" size={16}/></div>
          <div><strong>ECMO Certified</strong>Advanced life support</div>
        </div>
        <div className="ba-hero-float ba-hero-float-bot">
          <div className="ba-float-icon"><Icon name="building" size={16}/></div>
          <div><strong>Apollo Hospitals</strong>Critical Care Unit</div>
        </div>
      </div>
    </div>
  </div>
);

window.HeroBefore = HeroBefore;
window.HeroAfter = HeroAfter;
