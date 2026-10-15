import {useState, useEffect} from "@wordpress/element";
import Icon from "../../utils/Icon";
import {memo} from "@wordpress/element";
import DOMPurify from "dompurify";

const DataBreachConclusionItem = ({conclusion, delay}) => {
	const [generating, setGenerating] = useState(true);

	useEffect(() => {
		setTimeout(() => {
			show();
		}, delay);
	});

	const show = () => {
		setGenerating(false);
	}

	let iconColor = 'green';
	if (conclusion.report_status==='warning') iconColor = 'orange';
	if (conclusion.report_status==='error') iconColor = 'red';
	return (

		<>
			{generating &&
				<li className={"cmplz-conclusion__check icon-loading"}>
					<Icon name={'loading'} color={'grey'}/>
					<div className="cmplz-conclusion__check--report-text"> { conclusion.check_text} </div>
				</li>
			}
			{!generating &&
				<li className={"cmplz-conclusion__check icon-"+conclusion.report_status}>
					<Icon name={conclusion.report_status} color={iconColor}/>
					{/* nosemgrep: react-dangerouslysetinnerhtml */}
					<div className="cmplz-conclusion__check--report-text"
						 dangerouslySetInnerHTML={{__html: DOMPurify.sanitize( conclusion.report_text ) } } />
				</li>
			}
		</>
	)
}
export default memo(DataBreachConclusionItem);
