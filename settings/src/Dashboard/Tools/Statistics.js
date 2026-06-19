import Icon from "../../utils/Icon";
import {__} from "@wordpress/i18n";
import useStatistics from "../../Statistics/StatisticsData";
import {
	useEffect, useState
} from '@wordpress/element';

const Statistics = () => {
	const [data, setData] = useState(false);
	const [total, setTotal] = useState(0);
	const [fullConsent, setFullConsent] = useState(0);
	const [noConsent, setNoConsent] = useState(0);
	const [showPercentage, setShowPercentage] = useState({});
	const {consentType, statisticsData, loaded, fetchStatisticsData, labels, setLabels} = useStatistics();
	useEffect ( () => {
		if (!loaded && cmplz_settings.is_premium) {
			fetchStatisticsData();
		}
	},[]);

	useEffect(() => {
		if ( consentType==='' || !loaded ) {
			return;
		}

		if ( !statisticsData || !statisticsData.hasOwnProperty(consentType) ) {
			return;
		}

		// Set all labels without filtering
		setLabels(statisticsData[consentType]['labels']);
	},[loaded, consentType])

	useEffect(() => {
		if ( consentType==='' || !loaded || !statisticsData ) {
			return;
		}

		let data = statisticsData[consentType]['datasets'];
		setTotal(statisticsData[consentType]['total']);
		
		//get all datasets (banners)
		if (data.length > 0) {
			// Initialize arrays to store summed data
			let summedData = [];
			let totalFullConsent = 0;
			let totalNoConsent = 0;
			
			// Sum data from all banners
			data.forEach((dataset) => {
				let datasetData = dataset.data;
				
				// Initialize summedData array if it's empty
				if (summedData.length === 0) {
					summedData = new Array(datasetData.length).fill(0);
				}
				
				// Sum the data values
				datasetData.forEach((value, index) => {
					summedData[index] += parseInt(value);
				});
				
				// Sum the consent values
				totalFullConsent += parseInt(dataset.full_consent);
				totalNoConsent += parseInt(dataset.no_consent);
			});
			
			setFullConsent(totalFullConsent);
			setNoConsent(totalNoConsent);
			setData(summedData);
		}
	},[loaded, consentType])

	const getRowIcon = (index) => {
		let name = 'dial-med-low-light';
		if (index===1) {
			name = 'dial-med-light';
		} else if (index===2) {
			name = 'dial-light';
		}else if (index===3) {
			name = 'dial-off-light';
		} else if (index===4) {
			name = 'dial-min-light';
		} else if (index===5) {
			name = 'dial-low-light';
		} else if (index===6) {
			name = 'dial-med-light';
		}
		return (
			<>
				<Icon name = {name} color = 'black' />
			</>
		)
	}

	const getPercentage = (value) => {
		if (!value || value === 0) {
			return 0;
		}
		value = parseInt(value);
		return parseFloat(((value/total)*100).toFixed(1));
	}

	const handleCategoryData = (value, index) => {
		// If state doesn't exist for this index, default to showing percentage
		if (showPercentage[index] === undefined || showPercentage[index] === true) {
			return `${getPercentage(value)}%`;
		}
		return value;
	}

	const toggleItemDisplay = (index) => {
		setShowPercentage(prev => ({
			...prev,
			[index]: prev[index] === undefined ? false : !prev[index]
		}));
	}
 
	return (
		<div className="cmplz-statistics">
			<div className="cmplz-statistics-select">
			<div className="cmplz-statistics-select-item">
					<Icon name = "dial-max-light" color={"blue"} size="22"/>
					<h2>{total}</h2>
					<span>{__('Total', 'complianz-gdpr')}</span>
				</div>
				<div className="cmplz-statistics-select-item">
					<Icon name = "dial-med-light" color={"green"} size="22"/>
					<h2>{fullConsent}</h2>
					<span>{__('Full Consent', 'complianz-gdpr')}</span>
				</div>
				<div className="cmplz-statistics-select-item">
					<Icon name = "dial-min-light" color={"red"} size="22"/>
					<h2>{noConsent}</h2>
					<span>{__('No Consent', 'complianz-gdpr')}</span>
				</div>
			</div>
			<div className="cmplz-statistics-list">
				{labels.length > 0 && labels.map((label, index) =>
					<div className="cmplz-statistics-list-item" key={index}>
						{getRowIcon(index)}
						<p className="cmplz-statistics-list-item-text">{label}</p>
						<p className="cmplz-statistics-list-item-number" onClick={() => toggleItemDisplay(index)}>
							<span>{data.hasOwnProperty(index) && handleCategoryData(data[index], index)}</span>
						</p>
					</div>
				)}
				{labels.length === 0 && 
				<div className="cmplz-statistics-list-item" >
					<p className="cmplz-statistics-list-item-text">{__('No data.', 'complianz-gdpr')}</p>
				</div>
				}
			</div>
		</div>
	)
}
export default Statistics;
