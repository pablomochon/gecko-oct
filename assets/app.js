/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

import 'bootstrap';

import 'jquery';
// require jQuery normally
const $ = require('jquery');
// create global $ and jQuery variables
global.$ = global.jQuery = $;

import 'jquery-ui';
import 'jquery-ui/ui/widgets/sortable';

import '@popperjs/core';
import '@fortawesome/fontawesome-free/css/all.css';
import '@fortawesome/fontawesome-free/js/all.js';
import 'admin-lte/dist/css/adminlte.css';
import 'admin-lte/dist/js/adminlte.js';
import 'select2/dist/css/select2.css';
import '@ttskch/select2-bootstrap4-theme/dist/select2-bootstrap4.css';
import 'select2/dist/js/select2.js';
import 'jquery-knob/dist/jquery.knob.min.js';
// import 'admin-lte/plugins/jquery-knob/jquery.knob.min.js';

import './defaults.js'

import 'tabulator-tables/dist/css/tabulator_bootstrap4.css';
import 'daterangepicker/daterangepicker.css';

//console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
