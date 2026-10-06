const Hyperlink = ( props ) => {
	let label_pre = '';
	let label_post = '';
	let link_text = '';
	// Accept both %s...%s and numbered %1$s...%2$s placeholders.
	const placeholder = /%(?:\d+\$)?s/;
	if ( placeholder.test( props.text ) ) {
		const parts = props.text.split( placeholder );
		label_pre = parts[ 0 ];
		link_text = parts[ 1 ];
		label_post = parts[ 2 ];
	} else {
		link_text = props.text;
	}
	const className = props.className ? props.className : 'cmplz-link';

	return (
		<>
			{ label_pre }
			<a
				className={ className }
				target={ props.target }
				rel={ props.rel }
				href={ props.url }
			>
				{ link_text }
			</a>
			{ label_post }
		</>
	);
};
export default Hyperlink;
