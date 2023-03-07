<div class="backdrop"></div>
<div id="mapa-ciudados">
  <div id="map-wrapper">
    <div id="filters">
      <div class="filters-top wrapper">
        <div class="wrapper select-wrapper">
          <i class="fas fa-users"></i>
          <div id="objetivo-trigger" class="select-filter" onclick="toggleOptions('objetivo')">Población objetivo</div>
          <ul id="objetivo" class="select-options"></ul>
          <i class="fas fa-chevron-down right"></i>
        </div>
        <div class="wrapper">
          <i class="fas fa-cake"></i>
          <input type="text" id="edad" value="" placeholder="Edad"></input>
        </div>
        <div class="wrapper select-wrapper">
          <i class="fas fa-hand-holding-hand"></i>
          <div id="servicio-trigger" class="select-filter" onclick="toggleOptions('servicio')">Tipo de servicio</div>
          <ul id="servicio" class="select-options"></ul>
          <i class="fas fa-chevron-down right"></i>
        </div>
        <div class="wrapper">
          <i class="fas fa-filter-circle-xmark"></i>
          <div id="clear" class="button" onclick="clearFilters()">Borrar filtros</div>
        </div>
      </div>
      <div class="wrapper filters-bottom">
        <label><input type="checkbox" id="extendido" value="false"> Horario extendido</label>
        <label><input type="checkbox" id="verano" value="false"> Funciona en verano</label>
      </div>
      <div id="results" class="button"></div>
    </div>
    <div id="search">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" id="search-res" name="search-res" placeholder="Buscar..."></input>
    </div>
    <div id="map" style="height: 500px;"></div>
    <div id="no-results" style="display:none;">
      <span>No hay resultados para los filtros seleccionados</span>
      <i onclick="jQuery('#no-results').hide();" class="fa fa-close"></i>
    </div>
  </div>
  <div id="list"></div>
</div>
<?php
//Get .json file with the info
$path = wp_upload_dir(null,false);
$dir = dirname(dirname($path[basedir]));
$uploadDir = $dir . "/markersData.json";
?>
<script>
//Obtain data from the json file
var resources = <?php echo json_encode(file_get_contents($uploadDir)) ?>;
var data = JSON.parse(resources);
//window.elements = data;
const total = data.length;
const mapBounds = [];
const serv_options = [];
const obj_options = [];
var currentList = [];
var min_max_age = [];
var active_filters = {
 'serv': [],
 'obj': [],
 'age': -1,
 'summ': false,
 'ext': false,
 'search_res': ''
};
//MAP
var markers = L.layerGroup();
var map = L.map('map', {scrollWheelZoom: false}).setView([-34.897013, -56.171186], 13);
L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
   maxZoom: 19,
   subdomains: 'abcd',
   attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
}).addTo(map);
map.zoomControl.setPosition('topright');
//LIST
clearFilters(true);
//Add filters
var serv_options_dom = document.getElementById('servicio');
for (var i = 0; i < serv_options.length; i++){
 serv_options_dom.innerHTML += '<li class="option serv-option" id="serv-option-'+i+'" onclick="toggleOption(\'serv\', '+i+', this)">'+serv_options[i]+'</li>';
}
var obj_options_dom = document.getElementById('objetivo');
for (var i = 0; i < obj_options.length; i++){
  const style = get_style(obj_options[i]);
  obj_options_dom.innerHTML += '<li class="option obj-option" id="obj-option-'+i+'" onclick="toggleOption(\'obj\', '+i+', this)"><i style="color: '+style.color+'" class="fas fa-'+style.icon+'"></i>'+obj_options[i]+'</li>';
}
//Add listeners
jQuery('#extendido').change(() => {
 if(jQuery('#extendido').is(':checked')) {
   active_filters.ext = true;
 }
 else {
   active_filters.ext = false;
 }
 reloadFilters();
});
jQuery('#verano').change(() => {
 if( jQuery('#verano').is(':checked') ) {
   active_filters.summ = true;
 }
 else {
   active_filters.summ = false;
 }
 reloadFilters();
});
jQuery('#edad').change(() => {
 active_filters.age = jQuery('#edad').val();
 reloadFilters();
});
jQuery('#search-res').change(() => {
 active_filters.search_res = jQuery('#search-res').val();
 reloadFilters();
});
jQuery('.backdrop').click(() => {
 jQuery('#objetivo').hide();
 jQuery('#servicio').hide();
 jQuery('.backdrop').hide();
 reloadFilters();
});
//FUNCTIONS
//Display options
function toggleOptions(id) {
 var options = jQuery("#"+id);
 if ( options.is(":hidden") ) {
   options.show();
   jQuery('.backdrop').show();
 }
 else {
   reloadFilters();
   options.hide();
 }
}
//Filter by option
function toggleOption(cont, id) {
 var index = active_filters[cont].indexOf(id);
 if ( index !== -1 ) {
   active_filters[cont].splice(index, 1);
   jQuery('#'+cont+'-option-'+id).removeClass("active");
 }
 else {
   active_filters[cont].push(id);
   jQuery('#'+cont+'-option-'+id).addClass("active");
 }
}
// show description
function toggleDesc(id) {
 var srv = jQuery('#srv-'+id);
 if ( srv.hasClass('active') ) {
   jQuery('.srv').removeClass('active');
 }
 else {
   jQuery('.srv').removeClass('active');
   var position = jQuery('#srv-'+id).offset().top - jQuery('#srv-0').offset().top;
   jQuery("#list").animate({
     scrollTop: position
   }, 1000, 'linear' );
   //document.getElementById('srv-'+id).scrollIntoView({behavior: 'smooth'});
   srv.addClass("active");
   map.flyTo( [currentList[id].lat, currentList[id].lng], 18);
 }
}
// Click PIN
function clickPin(pin) {
 let pos = pin.target.options.list_pos;
 toggleDesc(pos);
}
//Clear filters
function clearFilters(initial = false) {
 document.getElementById("edad").value = '';
 document.getElementById("extendido").checked = false;
 document.getElementById("verano").checked = false;
 document.getElementById("search-res").value = '';

 renderElements(data, initial);
}
//FILTER
function reloadFilters() {
 // If fiter is set
 if ( active_filters['obj'].length > 0 || active_filters['serv'].length > 0  || active_filters['age'] >= 0 || active_filters['summ'] ||  active_filters['ext'] || active_filters['search_res'] != '') {
   result = [];
   active_obj = active_filters['obj'].map( x => obj_options[x]);
   active_serv = active_filters['serv'].map( x => serv_options[x]);
   data.forEach((element, i) => {
     var include = false;
     if ( active_filters['obj'].length > 0 ) {
       var match = false;
       active_obj.forEach((item) => {
         if ( element[item] == "TRUE") {
           match = true;
           return;
         }
       });
       if ( match ) {
         include = true;
       }
       else {
         include = false;
         return;
       }
     }
     if ( active_filters['serv'].length > 0 ) {
       if ( active_serv.includes(element.tipo_centro) ) {
         include = true;
       }
       else {
         include = false;
         return;
       }
     }
     if ( active_filters['age'] >= 0 ) {
       if ( element['edad_mínima'] <= active_filters['age'] && active_filters['age'] <= element['edad_máxima'] ) {
         include = true;
       }
       else {
         include = false;
         return;
       }
     }
     if ( active_filters['ext'] ) {
       if ( element.extendido == "TRUE" ) {
         include = true;
       }
       else {
         include = false;
         return;
       }
     }
     if ( active_filters['summ'] ) {
       if ( element.verano == "TRUE" ) {
         include = true;
       }
       else {
         include = false;
         return;
       }
     }
     if ( active_filters['search_res'] != '' ) {
       if ( Object.values(element).join(" ").toLowerCase().search(active_filters['search_res'].toLowerCase()) !== -1 ) {
         include = true;
       }
       else {
         include = false;
         return;
       }
     }
     if (include) {
       result.push(element);
     }
   });
 }
 else {
   result = data;
 }
 renderElements(result);
}
//Single element html
function elementListHtml(element, i) {
  const style = get_style(element.poblacion);
  return '<li onclick="toggleDesc('+ i +')" class="srv" id="srv-'+ i +'">\
     <div class="srv-header"><h2>'+element.NOMBRE+'</h2><i style="color: '+style.color+'" class="fas fa-'+style.icon+'"></i></div>\
     <div class="srv-body">\
       <div class="row"><h3>'+element.pub_priv_sc+' - '+element.tipo_centro+'</h3></div>\
       <div class="row"><i class="fas fa-location-dot"></i><span>'+element.NOM_CAL+', '+element.NUM_PUE+'</span></div>\
       <div class="row"><i class="fas fa-phone"></i><span>'+element.TELEFON+'</span></div>\
       <div class="row"><i class="fas fa-link"></i><span>'+element.PAG_RED+'</span></div>\
       <div class="row"><i class="fas fa-envelope"></i><span>'+element.CORREO+'</span></div>\
       <div class="row"><i class="fas fa-users"></i><span>'+element.POB_OBJ+'</span></div>\
       <div class="row"><p>'+element.SERV_PR+'</p></div>\
       <div class="row"><p>'+element.info+'</p></div>\
  </li>';
 }
 function get_style(element) {
   var res = {color: '#15BE5A', icon: 'person'};
   switch (element) {
     case 'Niños y niñas':
        res = {color: '#15BE5A', icon: 'child'};
        break;
      case 'Personas adultas mayores':
        res = {color: '#3E189B', icon: 'person-cane'};
        break;
      case 'Personas en situación de discapacidad intelectual':
        res = {color: '#FF702C', icon: 'brain'};
        break;
      case 'Personas con discapacidad':
      case 'Personas en situación de discapacidad':
        res = {color: '#FF702C', icon: 'wheelchair'};
        break;
      case 'Personas en situación de discapacidad visual':
        res = {color: '#FF702C', icon: 'person-walking-with-cane'};
        break;
      case 'Personas con dificultades en el aprendizaje':
        res = {color: '#FF702C', icon: 'book-open'};
        break;
    }
    return res;
 }
 function renderElements(elements, initial = false) {
   //Generate markers for all data rows |element = data[0]
   if ( elements.length > 0 ) {
     markers.clearLayers();
     jQuery('#no-results').hide();
     currentList = elements;
     var list = '<ul>';
     elements.forEach((element, i) => {
       list += elementListHtml(element, i);
       var iconUrl = '/wp-content/uploads/sites/5/2022/10/icon-green.png';
       switch (element.poblacion) {
         case 'Personas adultas mayores':
         iconUrl = '/wp-content/uploads/sites/5/2022/10/icon-blue.png';
         break;
         case 'Personas con discapacidad':
         case 'Personas en situación de discapacidad':
         case 'Personas en situación de discapacidad visual':
         case 'Personas en situación de discapacidad intelectual':
         case 'Personas con dificultades en el aprendizaje':
         iconUrl = '/wp-content/uploads/sites/5/2022/10/icon-orange.png';
         break;
       }
       var iconDefault = L.icon({
         className: "marker",
         iconUrl: iconUrl,
         iconSize: [29, 37],
         iconAnchor: [12, 37],
         popupAnchor: [1, -34],
         tooltipAnchor: [16, -28],
         shadowSize: [41, 41]
       });
       var mark = L.marker([element['lat'],element['lng']], {icon: iconDefault, list_pos: i}).on('click', clickPin).addTo(markers);
       mapBounds.push([element.lat, element.lng]);
       //Load filters if initial load
       if ( initial ) {
         if ( !serv_options.includes(element.tipo_centro) ) {
           serv_options.push(element.tipo_centro);
         }
         if ( !obj_options.includes(element.poblacion) ) {
           obj_options.push(element.poblacion);
         }
       }
     });
     list += '</ul>';
     map.addLayer(markers);
     document.getElementById("list").innerHTML = list;
     document.getElementById("results").innerHTML = "Mostrando "+elements.length+"/"+total+" puntos";
     map.flyToBounds(mapBounds);
   }
   else {
     jQuery('#no-results').show();
   }
 }
</script>
