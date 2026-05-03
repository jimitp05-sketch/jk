// Renders a "before" vs "after" pair side-by-side inside a canvas artboard.
// Reuses the kit's jk-* styles for visual continuity with the design system.
const Pair = ({ before, after, beforeLabel = 'Before', afterLabel = 'After' }) => (
  <div className="pair">
    <div className="pair-col pair-before">
      <span className="pair-tag pair-tag-before">{beforeLabel}</span>
      <div className="pair-frame">{before}</div>
    </div>
    <div className="pair-col pair-after">
      <span className="pair-tag pair-tag-after">{afterLabel}</span>
      <div className="pair-frame">{after}</div>
    </div>
  </div>
);

// Single annotated card explaining what changed and why
const RationaleCard = ({ priority, title, problems, fixes, lift }) => (
  <div className="rationale">
    <div className="rationale-head">
      <span className={`prio prio-${priority.toLowerCase()}`}>{priority}</span>
      <h3>{title}</h3>
    </div>
    <div className="rationale-body">
      <div className="rationale-col">
        <div className="rationale-label rationale-label-bad">Problems today</div>
        <ul>{problems.map((p, i) => <li key={i}>{p}</li>)}</ul>
      </div>
      <div className="rationale-col">
        <div className="rationale-label rationale-label-good">What we changed</div>
        <ul>{fixes.map((f, i) => <li key={i}>{f}</li>)}</ul>
      </div>
    </div>
    {lift && <div className="rationale-lift">Expected impact · <strong>{lift}</strong></div>}
  </div>
);

window.Pair = Pair;
window.RationaleCard = RationaleCard;
