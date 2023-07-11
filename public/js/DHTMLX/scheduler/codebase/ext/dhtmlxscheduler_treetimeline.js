/*

@license
dhtmlxScheduler v.5.3.10 Professional

This software is covered by DHTMLX Enterprise License. Usage without proper license is prohibited.

(c) XB Software Ltd.

*/
Scheduler.plugin(function(e){e.attachEvent("onTimelineCreated",function(t){"tree"==t.render&&(t.y_unit_original=t.y_unit,t.y_unit=e._getArrayToDisplay(t.y_unit_original),e.attachEvent("onOptionsLoadStart",function(){t.y_unit=e._getArrayToDisplay(t.y_unit_original)}),e.form_blocks[t.name]={render:function(e){return"<div class='dhx_section_timeline' style='overflow: hidden; height: "+e.height+"px'></div>"},set_value:function(t,a,n,i){
var r=e._getArrayForSelect(e.matrix[i.type].y_unit_original,i.type);t.innerHTML="";var o=document.createElement("select");t.appendChild(o);var _=t.getElementsByTagName("select")[0];!_._dhx_onchange&&i.onchange&&(_.onchange=i.onchange,_._dhx_onchange=!0);for(var d=0;d<r.length;d++){var s=document.createElement("option");s.value=r[d].key,s.value==n[e.matrix[i.type].y_property]&&(s.selected=!0),s.innerHTML=r[d].label,_.appendChild(s)}},get_value:function(e,t,a){return e.firstChild.value},
focus:function(e){}})}),e.attachEvent("onBeforeSectionRender",function(t,a,n){var i={};if("tree"==t){var r,o,_,d,s,l;d="dhx_matrix_scell",a.children?(r=n.folder_dy||n.dy,n.folder_dy&&!n.section_autoheight&&(_="height:"+n.folder_dy+"px;"),o="dhx_row_folder",d+=" folder",a.open?d+=" opened":d+=" closed",s="<div class='dhx_scell_expand'>"+(a.open?"-":"+")+"</div>",l=n.folder_events_available?"dhx_data_table folder_events":"dhx_data_table folder"):(r=n.dy,o="dhx_row_item",d+=" item",s="",
l="dhx_data_table"),d+=e.templates[n.name+"_scaley_class"](a.key,a.label,a)?" "+e.templates[n.name+"_scaley_class"](a.key,a.label,a):"";i={height:r,style_height:_,tr_className:o,td_className:d,td_content:"<div class='dhx_scell_level"+a.level+"'>"+s+"<div class='dhx_scell_name'>"+(e.templates[n.name+"_scale_label"](a.key,a.label,a)||a.label)+"</div></div>",table_className:l}}return i});var t;e.attachEvent("onBeforeEventChanged",function(a,n,i){
if(e._isRender("tree"))for(var r=e._get_event_sections?e._get_event_sections(a):[a[e.matrix[e._mode].y_property]],o=0;o<r.length;o++){var _=e.getSection(r[o]);if(_&&_.children&&!e.matrix[e._mode].folder_events_available)return i||(a[e.matrix[e._mode].y_property]=t),!1}return!0}),e.attachEvent("onBeforeDrag",function(a,n,i){if(e._isRender("tree")){var r,o=e._locate_cell_timeline(i);if(o&&(r=e.matrix[e._mode].y_unit[o.y].key,
e.matrix[e._mode].y_unit[o.y].children&&!e.matrix[e._mode].folder_events_available))return!1;var _=e.getEvent(a),d=e.matrix[e._mode].y_property;t=_&&_[d]?_[d]:r}return!0}),e._getArrayToDisplay=function(t){var a=[],n=function(t,i,r,o){for(var _=i||0,d=0;d<t.length;d++){var s=t[d];s.level=_,s.$parent=r||null,s.children&&void 0===s.key&&(s.key=e.uid()),o||a.push(s),s.children&&n(s.children,_+1,s.key,o||!s.open)}};return n(t),a},e._getArrayForSelect=function(t,a){var n=[],i=function(t){
for(var r=0;r<t.length;r++)e.matrix[a].folder_events_available?n.push(t[r]):t[r].children||n.push(t[r]),t[r].children&&i(t[r].children,a)};return i(t),n},e._toggleFolderDisplay=function(t,a,n){var i,r=function(e,t,a,n){for(var o=0;o<t.length&&(t[o].key!=e&&!n||!t[o].children||(t[o].open=void 0!==a?a:!t[o].open,i=!0,n||!i));o++)t[o].children&&r(e,t[o].children,a,n)},o=e.getSection(t);void 0!==a||n||(a=!o.open),
e.callEvent("onBeforeFolderToggle",[o,a,n])&&(r(t,e.matrix[e._mode].y_unit_original,a,n),e.matrix[e._mode].y_unit=e._getArrayToDisplay(e.matrix[e._mode].y_unit_original),e.callEvent("onOptionsLoad",[]),e.callEvent("onAfterFolderToggle",[o,a,n]))},e.attachEvent("onCellClick",function(t,a,n,i,r){e._isRender("tree")&&(e.matrix[e._mode].folder_events_available||void 0!==e.matrix[e._mode].y_unit[a]&&e.matrix[e._mode].y_unit[a].children&&e._toggleFolderDisplay(e.matrix[e._mode].y_unit[a].key))}),
e.attachEvent("onYScaleClick",function(t,a,n){e._isRender("tree")&&a.children&&e._toggleFolderDisplay(a.key)}),e.getSection=function(t){if(e._isRender("tree")){var a,n=function(e,t){for(var i=0;i<t.length;i++)t[i].key==e&&(a=t[i]),t[i].children&&n(e,t[i].children)};return n(t,e.matrix[e._mode].y_unit_original),a||null}},e.deleteSection=function(t){if(e._isRender("tree")){var a=!1,n=function(e,t){for(var i=0;i<t.length&&(t[i].key==e&&(t.splice(i,1),a=!0),!a);i++)t[i].children&&n(e,t[i].children)}
;return n(t,e.matrix[e._mode].y_unit_original),e.matrix[e._mode].y_unit=e._getArrayToDisplay(e.matrix[e._mode].y_unit_original),e.callEvent("onOptionsLoad",[]),a}},e.deleteAllSections=function(){e._isRender("tree")&&(e.matrix[e._mode].y_unit_original=[],e.matrix[e._mode].y_unit=e._getArrayToDisplay(e.matrix[e._mode].y_unit_original),e.callEvent("onOptionsLoad",[]))},e.addSection=function(t,a){if(e._isRender("tree")){var n=!1,i=function(e,t,r){
if(a)for(var o=0;o<r.length&&(r[o].key==t&&r[o].children&&(r[o].children.push(e),n=!0),!n);o++)r[o].children&&i(e,t,r[o].children);else r.push(e),n=!0};return i(t,a,e.matrix[e._mode].y_unit_original),e.matrix[e._mode].y_unit=e._getArrayToDisplay(e.matrix[e._mode].y_unit_original),e.callEvent("onOptionsLoad",[]),n}},e.openAllSections=function(){e._isRender("tree")&&e._toggleFolderDisplay(1,!0,!0)},e.closeAllSections=function(){e._isRender("tree")&&e._toggleFolderDisplay(1,!1,!0)},
e.openSection=function(t){e._isRender("tree")&&e._toggleFolderDisplay(t,!0)},e.closeSection=function(t){e._isRender("tree")&&e._toggleFolderDisplay(t,!1)}});
//# sourceMappingURL=../sources/ext/dhtmlxscheduler_treetimeline.js.map