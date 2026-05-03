// IMPROVEMENT 5 — PAGE FLOW: 12-section CV order vs Crisis→Reassurance→Proof→Action
const flowSteps = (variant) => {
  if (variant === 'before') return [
    { label: 'Hero · headline + stats', tag: 'hero' },
    { label: 'About · biography & schools', tag: 'about' },
    { label: 'Expertise · 6 procedures', tag: 'expertise' },
    { label: 'Why Dr. K · 4 reasons', tag: 'why' },
    { label: 'Process · how an ICU admit works', tag: 'process' },
    { label: 'Timeline · 30 yr career', tag: 'timeline' },
    { label: 'Clinical scenarios', tag: 'scenarios' },
    { label: 'Knowledge Hub preview', tag: 'edu' },
    { label: 'FAQ', tag: 'faq' },
    { label: 'Contact form', tag: 'contact', conv: true },
    { label: 'Footer', tag: 'foot' },
  ];
  return [
    { label: 'Crisis bar · phone visible', tag: 'crisis', conv: true },
    { label: 'Hero · empathy → call CTA', tag: 'hero', conv: true },
    { label: 'Triage · call / WA / OPD cards', tag: 'triage', conv: true },
    { label: 'Trust · ratings + comparison', tag: 'trust' },
    { label: 'Pulse · review wall', tag: 'pulse' },
    { label: '60-sec doctor video', tag: 'video' },
    { label: 'Expertise · ECMO / CRRT', tag: 'expertise' },
    { label: 'About · short story', tag: 'about' },
    { label: 'Knowledge Hub · 3 cards', tag: 'edu' },
    { label: 'FAQ + WhatsApp CTA', tag: 'faq', conv: true },
    { label: 'Footer · emergency strip', tag: 'foot', conv: true },
  ];
};

const FlowMap = ({ variant }) => {
  const steps = flowSteps(variant);
  return (
    <div className={`ba-flow ba-flow-${variant}`}>
      {steps.map((s, i) => (
        <div key={i} className={`ba-flow-step ${s.conv ? 'ba-flow-conv' : ''}`}>
          <div className="ba-flow-num">{String(i+1).padStart(2,'0')}</div>
          <div className="ba-flow-label">{s.label}</div>
          {s.conv && <span className="ba-flow-pill"><Icon name="phone" size={9}/> conversion</span>}
        </div>
      ))}
      <div className="ba-flow-summary">
        {variant === 'before' ? (
          <><strong>9 sections</strong> before any conversion path. Biographical CV order. Education sits before action.</>
        ) : (
          <><strong>3 conversion paths</strong> in the first viewport. Crisis → Reassurance → Proof → Action. Education comes last.</>
        )}
      </div>
    </div>
  );
};

const FlowBefore = () => <FlowMap variant="before"/>;
const FlowAfter = () => <FlowMap variant="after"/>;

window.FlowBefore = FlowBefore;
window.FlowAfter = FlowAfter;
