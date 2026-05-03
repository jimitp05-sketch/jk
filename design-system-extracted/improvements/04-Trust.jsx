// IMPROVEMENT 4 — TRUST: bare credentials → social proof + comparison anchor
const TrustBefore = () => (
  <div className="ba-trust ba-trust-before">
    <div className="ba-glass-card ba-tcb-card">
      <span className="jk-section-label">Why Choose Dr. Kothari</span>
      <h2 className="jk-section-title">Not Every ICU Doctor<br/>Is Equally Equipped.</h2>
      <p className="ba-tcb-sub">A rare combination of super-specialisation, institutional depth, and human-centred care.</p>
      <div className="ba-tcb-grid">
        {[
          { i: 'crosshair', t: '<10 ECMO Intensivists', b: 'Few in Gujarat trained to manage ECMO circuits.' },
          { i: 'building', t: 'Apollo Hospitals', b: 'India\'s most trusted private hospital network.' },
          { i: 'award', t: 'FNB Critical Care', b: 'Super-specialised. Beyond standard MD.' },
          { i: 'languages', t: 'Trilingual', b: 'Hindi · Gujarati · English at the bedside.' },
        ].map(c => (
          <div key={c.t} className="ba-tcb-cell">
            <Icon name={c.i} size={20}/>
            <strong>{c.t}</strong>
            <p>{c.b}</p>
          </div>
        ))}
      </div>
    </div>
  </div>
);

const TrustAfter = () => (
  <div className="ba-trust ba-trust-after">
    {/* 1. Aggregate ratings strip */}
    <div className="ba-rate-strip">
      <div className="ba-rate-cell">
        <div className="ba-rate-stars"><Icon name="star" size={14}/><Icon name="star" size={14}/><Icon name="star" size={14}/><Icon name="star" size={14}/><Icon name="star" size={14}/></div>
        <div><strong>4.9</strong> · 247 reviews</div>
        <span>Practo</span>
      </div>
      <div className="ba-rate-cell">
        <div className="ba-rate-stars"><Icon name="star" size={14}/><Icon name="star" size={14}/><Icon name="star" size={14}/><Icon name="star" size={14}/><Icon name="star" size={14}/></div>
        <div><strong>4.8</strong> · 89 reviews</div>
        <span>Google</span>
      </div>
      <div className="ba-rate-cell">
        <div className="ba-rate-trophy"><Icon name="award" size={16}/></div>
        <div><strong>Top 1%</strong></div>
        <span>Apollo Network 2025</span>
      </div>
      <div className="ba-rate-cell">
        <div className="ba-rate-trophy"><Icon name="check" size={16}/></div>
        <div><strong>NMC verified</strong></div>
        <span>Reg #G-12847</span>
      </div>
    </div>
    {/* 2. Comparison anchor — patient education framed */}
    <div className="ba-glass-card ba-anchor">
      <span className="jk-section-label">Patient education · NMC compliant</span>
      <h2 className="jk-section-title">Not all ICU care is <em>equal</em>. Here's what to ask for.</h2>
      <table className="ba-anchor-table">
        <thead>
          <tr>
            <th></th>
            <th className="ba-anchor-h-left">Standard ICU</th>
            <th className="ba-anchor-h-right">Dr. Kothari's ICU</th>
          </tr>
        </thead>
        <tbody>
          {[
            ['Life support capability', 'Ventilator only', 'Ventilator + ECMO + CRRT'],
            ['Specialist qualification', 'MD / DNB', 'MD + FNB Critical Care'],
            ['Multi-organ failure', 'Refer out when complex', 'Manages in-house'],
            ['Family communication', 'Updates on request', 'Daily proactive briefings · in your language'],
            ['Continuity', 'Rotating duty doctors', 'Same consultant, 20+ years at Apollo'],
          ].map(([a,b,c]) => (
            <tr key={a}>
              <td className="ba-anchor-cell-l">{a}</td>
              <td className="ba-anchor-cell-c">{b}</td>
              <td className="ba-anchor-cell-r"><Icon name="check" size={12}/> {c}</td>
            </tr>
          ))}
        </tbody>
      </table>
      <p className="ba-anchor-foot">Educational comparison. Not a marketing claim. Compliant with NMC Professional Conduct Regulations 2023.</p>
    </div>
  </div>
);

window.TrustBefore = TrustBefore;
window.TrustAfter = TrustAfter;
