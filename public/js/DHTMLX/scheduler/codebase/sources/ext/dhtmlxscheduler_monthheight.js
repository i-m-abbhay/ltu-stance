/*

@license
dhtmlxScheduler v.5.3.10 Professional

This software is covered by DHTMLX Enterprise License. Usage without proper license is prohibited.

(c) XB Software Ltd.

*/
Scheduler.plugin(function(scheduler){

scheduler.attachEvent("onTemplatesReady",function(){
	scheduler.xy.scroll_width = 0;

	var old = scheduler.render_view_data;
	scheduler.render_view_data=function(){
		var data = this._els["dhx_cal_data"][0];
		data.firstChild._h_fix = true;

		old.apply(scheduler,arguments);



		var height = parseInt(data.style.height);
		data.style.height="1px";
		data.style.height=data.scrollHeight+"px";

		this._obj.style.height = this._obj.clientHeight + data.scrollHeight - height + "px";
	};

	var old_s=scheduler._reset_month_scale;
	scheduler._reset_month_scale=function(a,b,c,d){
		var dummy = {clientHeight:100};
		old_s.apply(scheduler,[dummy,b,c,d]);
		a.innerHTML = dummy.innerHTML;
	};

});

});