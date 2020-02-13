import { TabPanel, Dashicon, Button } from '@wordpress/components';

import ReactDOM from "react-dom";

const onSelect = ( tabName ) => {
    console.log( 'Selecting tab', tabName );
};

const MyTabPanel = () => (
	<TabPanel className="my-tab-panel"
		activeClass="active-tab"
		onSelect={ onSelect }
		tabs={ [
			{
				name: 'tab1',
				title: 'Tab 1',
				className: 'tab-one',
			},
			{
				name: 'tab2',
				title: 'Tab 2',
				className: 'tab-two',
			},
		] }>
		{
			( tab ) => <p>{ tab.title }</p>
		}
	</TabPanel>
);

ReactDOM.render(
	<div>
		<MyTabPanel />
		<Button isPrimary>
			Click me!
		</Button>
		<div>
			<Dashicon icon="admin-home" />
			<Dashicon icon="products" />
			<Dashicon icon="wordpress" />
		</div>
	</div>,
	document.getElementById( 'bg-nav-component' )
);