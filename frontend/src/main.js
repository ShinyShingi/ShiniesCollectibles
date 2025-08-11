import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import './style.css';
import App from './App.vue';

// PrimeVue
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import MultiSelect from 'primevue/multiselect';
import Calendar from 'primevue/calendar';
import InputNumber from 'primevue/inputnumber';
import Tag from 'primevue/tag';
import Chip from 'primevue/chip';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import RadioButton from 'primevue/radiobutton';
import Card from 'primevue/card';
import Toolbar from 'primevue/toolbar';
import SplitButton from 'primevue/splitbutton';
import Menu from 'primevue/menu';
import Badge from 'primevue/badge';
import Tooltip from 'primevue/tooltip';

// PrimeVue Theme (v4 uses different approach)
import 'primeicons/primeicons.css';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: Aura
    }
});

// Register PrimeVue components globally
app.component('DataTable', DataTable);
app.component('Column', Column);
app.component('InputText', InputText);
app.component('Dropdown', Dropdown);
app.component('Button', Button);
app.component('MultiSelect', MultiSelect);
app.component('Calendar', Calendar);
app.component('InputNumber', InputNumber);
app.component('PrimeTag', Tag);
app.component('Chip', Chip);
app.component('Dialog', Dialog);
app.component('Textarea', Textarea);
app.component('Checkbox', Checkbox);
app.component('RadioButton', RadioButton);
app.component('Card', Card);
app.component('Toolbar', Toolbar);
app.component('SplitButton', SplitButton);
app.component('Menu', Menu);
app.component('Badge', Badge);

// Register directives
app.directive('tooltip', Tooltip);

app.mount('#app');