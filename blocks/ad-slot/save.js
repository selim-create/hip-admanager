/**
 * Ad Slot Block - Save Component
 */
export default function save({ attributes }) {
	const { slotId, placement, alignment } = attributes;

	// Save as data attributes for frontend processing
	return (
		<div
			className={`hip-ad-injection align-${alignment}`}
			data-hip-ad-slot={slotId}
			data-hip-ad-placement={placement}
			data-hip-ad-alignment={alignment}
		>
			{/* Ad slot marker: HIP_AD_SLOT:{slotId} */}
		</div>
	);
}
