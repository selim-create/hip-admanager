/**
 * Ad Slot Block - Edit Component
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, PanelBody, Notice } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

export default function Edit({ attributes, setAttributes }) {
	const { slotId, placement, alignment } = attributes;

	// Fetch ad slots from WordPress
	const { slots, isResolving } = useSelect((select) => {
		const { getEntityRecords, isResolving: checkResolving } = select(coreStore);
		
		return {
			slots: getEntityRecords('postType', 'hip_ad_slot', {
				per_page: -1,
				status: 'publish',
			}),
			isResolving: checkResolving('getEntityRecords', [
				'postType',
				'hip_ad_slot',
				{ per_page: -1, status: 'publish' },
			]),
		};
	}, []);

	// Prepare options for select control
	const slotOptions = [
		{ label: __('Select an ad slot', 'hip-admanager'), value: '' },
	];

	if (slots && slots.length > 0) {
		slots.forEach((slot) => {
			slotOptions.push({
				label: slot.title.rendered || `Slot #${slot.id}`,
				value: slot.id.toString(),
			});
		});
	}

	const selectedSlot = slots?.find((slot) => slot.id.toString() === slotId);

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Ad Slot Settings', 'hip-admanager')}>
					<SelectControl
						label={__('Select Ad Slot', 'hip-admanager')}
						value={slotId}
						options={slotOptions}
						onChange={(value) => setAttributes({ slotId: value })}
						help={__('Choose which ad slot to display', 'hip-admanager')}
					/>
					<SelectControl
						label={__('Placement', 'hip-admanager')}
						value={placement}
						options={[
							{ label: __('In-Content', 'hip-admanager'), value: 'in-content' },
							{ label: __('Above Content', 'hip-admanager'), value: 'above-content' },
							{ label: __('Below Content', 'hip-admanager'), value: 'below-content' },
						]}
						onChange={(value) => setAttributes({ placement: value })}
					/>
					<SelectControl
						label={__('Alignment', 'hip-admanager')}
						value={alignment}
						options={[
							{ label: __('Left', 'hip-admanager'), value: 'left' },
							{ label: __('Center', 'hip-admanager'), value: 'center' },
							{ label: __('Right', 'hip-admanager'), value: 'right' },
						]}
						onChange={(value) => setAttributes({ alignment: value })}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...useBlockProps()}>
				<div className={`hip-ad-placeholder align-${alignment}`} style={{
					padding: '20px',
					border: '2px dashed #ccc',
					backgroundColor: '#f9f9f9',
					textAlign: alignment,
					minHeight: '100px',
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'center',
				}}>
					{isResolving && (
						<p>{__('Loading ad slots...', 'hip-admanager')}</p>
					)}
					{!isResolving && !slotId && (
						<p>📢 {__('Please select an ad slot from the sidebar', 'hip-admanager')}</p>
					)}
					{!isResolving && slotId && selectedSlot && (
						<div>
							<p style={{ margin: '0 0 10px 0', fontSize: '14px', fontWeight: 'bold' }}>
								📢 {__('Ad Slot:', 'hip-admanager')} {selectedSlot.title.rendered}
							</p>
							<p style={{ margin: 0, fontSize: '12px', color: '#666' }}>
								{__('ID:', 'hip-admanager')} {slotId} | {__('Placement:', 'hip-admanager')} {placement}
							</p>
						</div>
					)}
					{!isResolving && slotId && !selectedSlot && (
						<Notice status="warning" isDismissible={false}>
							{__('Selected ad slot not found', 'hip-admanager')}
						</Notice>
					)}
				</div>
			</div>
		</>
	);
}
