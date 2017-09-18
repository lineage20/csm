    var newDay;
    var newMonth;
    var newYear;
    var newDayOfWeek;
    var now = new Date(); //当前日期
    var nowDayOfWeek = now.getDay(); //今天本周的第几天
    var nowDay = now.getDate(); //当前日
    var nowMonth = now.getMonth(); //当前月
    var nowYear = now.getYear(); //当前年
    nowYear += (nowYear < 2000) ? 1900 : 0; //
    var lastMonthDate = new Date(); //上月日期
    lastMonthDate.setDate(1);
    lastMonthDate.setMonth(lastMonthDate.getMonth() - 1);
    var lastYear = lastMonthDate.getYear();
    var lastMonth = lastMonthDate.getMonth();
    //格式化日期：yyyy-MM-dd
    function formatDate(date) {
        var myyear = date.getFullYear();
        var mymonth = date.getMonth() + 1;
        var myweekday = date.getDate();
        if (parseInt(mymonth) < 10) {
            mymonth = "0" + parseInt(mymonth);
        }
        if (parseInt(myweekday) < 10) {
            myweekday = "0" + parseInt(myweekday);
        }
        return (myyear + "-" + mymonth + "-" + myweekday);
    }

	//获得本周的日期
	var weekStartDate = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek+1);
    var now = new Date(); //当前日期
    var nowDayOfWeek = now.getDay(); //今天本周的第几天
    var nowDay = now.getDate(); //当前日
    var nowMonth = now.getMonth(); //当前月
    var nowYear = now.getYear(); //当前年
    nowYear += (nowYear < 2000) ? 1900 : 0; //
    var lastMonthDate = new Date(); //上月日期
    lastMonthDate.setDate(1);
    lastMonthDate.setMonth(lastMonthDate.getMonth() - 1);
    var lastYear = lastMonthDate.getYear();
    var lastMonth = lastMonthDate.getMonth();
    //格式化日期：yyyy-MM-dd
    function formatDate(date) {
        var myyear = date.getFullYear();
        var mymonth = date.getMonth() + 1;
        var myweekday = date.getDate();
        if (parseInt(mymonth) < 10) {
            mymonth = "0" + parseInt(mymonth);
        }
        if (parseInt(myweekday) < 10) {
            myweekday = "0" + parseInt(myweekday);
        }
        return (myyear + "-" + mymonth + "-" + myweekday);
    }

	//获得本周的日期
	var weekStartDate = new Date(nowYear, nowMonth, nowDay - nowDayOfWeek+1);
	var myDays=new Array();
	var week_day_arr = ['一','二','三','四','五','六','日'];
    var geturldate = $.query.get('date');
	$(function(){
        
	if (geturldate==''||typeof(geturldate) == "undefined") {
		      if (nowDayOfWeek==0) {
              for(var k=0;k<week_day_arr.length;k++){
				myDays[k]=[formatDate(new Date(nowYear, nowMonth, nowDay - nowDayOfWeek+k+1-7)),week_day_arr[k]];
		     }
            }
			else{
				for(var k=0;k<week_day_arr.length;k++){
				myDays[k]=[formatDate(new Date(nowYear, nowMonth, nowDay - nowDayOfWeek+k+1)),week_day_arr[k]];
		        }
		    }
		//$.each()遍历数组
		$.each(myDays,function(key,val){
			$('.news2_nr_top_box li.w'+(key+1)).html("<a href='#' >"+myDays[key][0]+"<span>星期"+myDays[key][1]+"</span></a>");
			$('.news2_nr_top_box li.w'+(key+1)+'>a').attr({'href':'/calendar/index/'+myDays[key][0]});
		});

		$('.news2_nr_top_box li').click(function(){
			$('.news2_nr_top_box li>a').removeClass("on");
			$(this).find('a').addClass("hovera");
			//两个日历同步
			var searchval = $(this).find('a:not(span)').text().substr(0,10);
			$('#datetimepicker8').val(searchval);
			ajax_ashx($('#datetimepicker8').val(),"all");//点击日历请求获取数据
		});
        //以下两行为input时间框初始值及高亮显示当前日期
        if (nowDayOfWeek==0) {
            $('.news2_nr_top_box li.w'+(nowDayOfWeek+7)+'>a').addClass("hovera");
        }else{
        	$('.news2_nr_top_box li.w'+nowDayOfWeek+'>a').addClass("hovera");
        }


        if (parseInt(nowMonth)<9) {
         var changemonth ='0'+(parseInt(nowMonth)+1);
        }
        else{
         var changemonth = (parseInt(nowMonth)+1);
        }
        if (parseInt(nowDay)<10) {
         var changeday = '0'+nowDay;
        }
        else{
         var changeday = nowDay;
        }
		$('#datetimepicker8').val(nowYear+'-'+changemonth+'-'+changeday);



		// $('#ifr_fe').attr({'src':'<!--#echo var="HX9999_HTML_HOST"-->/<!--#echo var="LANG"-->/fe_calendar.html?date='+formatDate(new Date(nowYear, nowMonth, nowDay))});
		//两个日期联动
		$('#search').click(function(){
        var t = $('#datetimepicker8').val();
        // $('#nowDate').val(t);
          newDay = t.substr(8,2); //选择前日
          newMonth = t.substr(5,2); //选择前月
          newYear = t.substr(0,4); //选择前年
          var ddofweek = new Date(newYear,newMonth-1,newDay);
          newDayOfWeek = ddofweek.getDay();
          liandongDate();
          ajax_ashx(t,"all");
		});

		//以上为日历时间控件设置等,以下为数据请求获取及显示
		// $date=QueryString("date");
		// if ($date){}
		ajax_ashx($('#datetimepicker8').val(),"all");
		}

		else{//刷新页面后以页面时间参数为准，重新赋值

			nowDay = geturldate.substr(8,2); //选择前日
            nowMonth = geturldate.substr(5,2); //选择前月
            nowYear = geturldate.substr(0,4); //选择前年
            var aaofweek = new Date(nowYear,nowMonth-1,nowDay);
            nowDayOfWeek = aaofweek.getDay();
            if (nowDayOfWeek==0) {
              for(var k=0;k<week_day_arr.length;k++){
				myDays[k]=[formatDate(new Date(nowYear, nowMonth-1, nowDay - nowDayOfWeek+k+1-7)),week_day_arr[k]];
		     }
            }
			else{
				for(var k=0;k<week_day_arr.length;k++){
				myDays[k]=[formatDate(new Date(nowYear, nowMonth-1, nowDay - nowDayOfWeek+k+1)),week_day_arr[k]];
		        }
		    }
		//$.each()遍历数组
		$.each(myDays,function(key,val){
			$('.news2_nr_top_box li.w'+(key+1)).html("<a href='#' >"+myDays[key][0]+"<span>星期"+myDays[key][1]+"</span></a>");
			$('.news2_nr_top_box li.w'+(key+1)+'>a').attr({'href':'/calendar/index/'+myDays[key][0]});
		});

		$('.news2_nr_top_box li').click(function(){
			$('.news2_nr_top_box li>a').removeClass("on");
			$(this).find('a').addClass("hovera");
			//两个日历同步
			var searchval = $(this).find('a:not(span)').text().substr(0,10);
			$('#datetimepicker8').val(searchval);
			ajax_ashx($('#datetimepicker8').val(),"all");
		});
        //以下两行为input时间框初始值及高亮显示当前日期
        if (nowDayOfWeek==0) {
         $('.news2_nr_top_box li.w'+(nowDayOfWeek+7)+'>a').addClass("hovera");
        }
        else{
        	$('.news2_nr_top_box li.w'+nowDayOfWeek+'>a').addClass("hovera");
        }

		if (parseInt(nowMonth)<10) {
         var changemonth ='0'+parseInt(nowMonth);
        }
        else{
         var changemonth = parseInt(nowMonth);
        }
        if (parseInt(nowDay)<10) {
         var changeday = '0'+parseInt(nowDay);
        }
        else{
         var changeday = parseInt(nowDay);
        }
		$('#datetimepicker8').val(nowYear+'-'+changemonth+'-'+changeday);
		$('#ifr_fe').attr({'src':'calendar.html?date='+formatDate(new Date(nowYear, nowMonth, nowDay))});
		//两个日期联动
		$('#search').click(function(){
        var t = $('#datetimepicker8').val();
        // $('#nowDate').val(t);
          newDay = t.substr(8,2); //选择前日
          newMonth = t.substr(5,2); //选择前月
          newYear = t.substr(0,4); //选择前年
          var ddofweek = new Date(newYear,newMonth-1,newDay);
          newDayOfWeek = ddofweek.getDay();
          liandongDate();
          ajax_ashx(t,"all");
		});
		ajax_ashx($('#datetimepicker8').val(),"all");
		}

		// //以上为日历时间控件设置等,以下为数据请求获取及显示
		// $date=QueryString("date");
		// if ($date){}
		// ajax_ashx(geturldate);
	});
    function liandongDate(){
		for(var k=0;k<week_day_arr.length;k++){
				myDays[k]=[formatDate(new Date(newYear, newMonth-1, newDay - newDayOfWeek+k+1)),week_day_arr[k]];
		}

		//$.each()遍历数组
		$.each(myDays,function(key,val){
			if((key+1)==newDayOfWeek){
             $('.news2_nr_top_box li.w'+(key+1)).html("<a href='#'  class='on'>"+myDays[key][0]+"<span>星期"+myDays[key][1]+"</span></a>");
			$('.news2_nr_top_box li.w'+(key+1)+'>a').attr({'href':'/calendar/index/'+myDays[key][0]});
			}
			else{
				$('.news2_nr_top_box li.w'+(key+1)).html("<a href='#' >"+myDays[key][0]+"<span>星期"+myDays[key][1]+"</span></a>");
			$('.news2_nr_top_box li.w'+(key+1)+'>a').attr({'href':'/calendar/index/'+myDays[key][0]});
			}
		});
    }
function QueryString(item){
	var serachurl = $('.news2_nr_top_box li>a').attr('href');
	var sValue=serachurl.match(new RegExp("[\?\&]"+item+"=([^\&]*)(\&?)","i"));
	return sValue?sValue[1]:sValue;
}

function ajax_ashx(date,country) {
    $.post('/calendar/json/'+date,
		{nowDate:date},
		function(data){
			$(".fe_loading").hide();
			$("#rili_tab01 tr:first").siblings().remove();
			$("#rili_tab02 tr:first").siblings().remove();
			$("#rili_tab03 tr").empty();
			var ret = typeof(data.finance.financeEventList) == "undefined"?'':data.finance.financeEventList;
			var rea = typeof(data.finance.financeVacationList) == "undefined"?'':data.finance.financeVacationList;
			var res = typeof(data.finance.financeDataList) == "undefined"?'':data.finance.financeDataList;
			if (ret!='') {
               
				if (typeof(ret.length)=='undefined') {
					var retRow = "<tr><td width='139' height='50' align='left' bgcolor='#f4f8fb'><span>"+ret["eventTime"]+"</span><span style='padding-left:20px;'>"+rebackcounty(ret["eventCountry"])+"</span></td>\n\
            <td width='121' height='50' align='left' bgcolor='#f4f8fb' class='star'>"+ret["importance"]+"</td><td width='751' height='50' align='left' bgcolor='#f4f8fb'>"+ret["eventContent"]+"</td></tr>";
					$("#rili_tab01").append(retRow);
				}else{
					for(var i=0;i<ret.length;i++){
					var retRow = "<tr><td width='139' height='50' align='left' bgcolor='#f4f8fb'><span>"+ret[i]["eventTime"]+"</span><span style='padding-left:20px;'>"+rebackcounty(ret[i]["eventCountry"])+"</span></td>\n\
            <td width='121' height='50' align='left' bgcolor='#f4f8fb' class='star'>"+ret[i]["importance"]+"</td><td width='751' height='50' align='left' bgcolor='#f4f8fb'>"+ret[i]["eventContent"]+"</td></tr>";
					$("#rili_tab01").append(retRow);
					}
				}
			}
			else{
				var retRow ="<tr><td>"+'今日无重要数据公布！'+"</td><td></td><td></td></tr>";
				$("#rili_tab01").append(retRow);
			}
			if (rea!='') {
				if (typeof(rea.length)=='undefined') {
					var reaRow = "<tr><td width='88' height='50' align='left' bgcolor='#f4f8fb'><div class='news2_jiaq-time'><span class='sp-tfon'>"+rea["vacationDate"].substr(0,4)+"</span><span>"+rea["vacationDate"].substr(5,5)+"</span></div></td>\n\
                                        <td width='57' height='50' align='left' bgcolor='#f4f8fb'>"+rebackcounty(rea["vacationCountry"])+"</td>\n\
                                        <td width='866' height='50' align='left' bgcolor='#f4f8fb'>"+rea["vacationContent"]+"</td></tr>";
					$("#rili_tab02").append(reaRow);
				}else{
					for(var i=0;i<rea.length;i++){
					var reaRow = "<tr><td width='88' height='50' align='left' bgcolor='#f4f8fb'><div class='news2_jiaq-time'><span class='sp-tfon'>"+rea[i]["vacationDate"].substr(0,4)+"</span><span>"+rea[i]["vacationDate"].substr(5,5)+"</span></div></td>\n\
                                        <td width='57' height='50' align='left' bgcolor='#f4f8fb'>"+rebackcounty(rea[i]["vacationCountry"])+"</td>\n\
                                        <td width='866' height='50' align='left' bgcolor='#f4f8fb'>"+rea[i]["vacationContent"]+"</td></tr>";
					$("#rili_tab02").append(reaRow);
					}
				}
			}
			else{
				var reaRow ="<tr><td>"+'今日无重要数据公布！'+"</td><td></td><td></td></tr>";
				$("#rili_tab02").append(reaRow);
			}
			if (res!='') {
                            if(country == "all"){
				if (typeof(res.length)=='undefined') {
				var newRow = "<tr>\n\
                                <td width='129' align='left' class='number1'><span>"+res["fdTime"]+"</span><span style='padding-left:20px;'>"+rebackcounty(res["fdCountry"])+"</span></td>\n\
                                <td width='191' height='50' align='left' bgcolor='#f4f8fb'>"+res["fdTitle"]+"</td>\n\
                                <td width='85' height='50' align='center' bgcolor='#f4f8fb' class='star'>"+importantLevel(res["importance"])+"</td>\n\
                                <td width='80' height='50' align='center' bgcolor='#f4f8fb'>"+res["lastValue"]+"</td>\n\
                                <td width='94' height='50' align='center' bgcolor='#f4f8fb'>"+res["prediction"]+"</td>\n\
                                <td width='88' height='50' align='center' bgcolor='#f4f8fb'>"+res["actual"]+"</td>\n\
                                <td width='178' height='50' align='center' bgcolor='#f4f8fb'>GBP<strong class='red'>利空</strong></td>\n\
                                <td width='166' height='50' align='center' bgcolor='#f4f8fb'>NZD<strong class='green'>利多</strong></td></tr>";
				$("#rili_tab03").append(newRow);
				}else{
					for(var i=0;i<res.length;i++){
						var newRow = "<tr>\n\
                                <td width='129' align='left' class='number1'><span>"+res[i]["fdTime"]+"</span><span style='padding-left:20px;'>"+rebackcounty(res[i]["fdCountry"])+"</span></td>\n\
                                <td width='191' height='50' align='left' bgcolor='#f4f8fb'>"+res[i]["fdTitle"]+"</td>\n\
                                <td width='85' height='50' align='center' bgcolor='#f4f8fb' class='star'>"+importantLevel(res[i]["importance"])+"</td>\n\
                                <td width='80' height='50' align='center' bgcolor='#f4f8fb'>"+res[i]["lastValue"]+"</td>\n\
                                <td width='94' height='50' align='center' bgcolor='#f4f8fb'>"+res[i]["prediction"]+"</td>\n\
                                <td width='88' height='50' align='center' bgcolor='#f4f8fb'>"+res[i]["actual"]+"</td>\n\
                                <td width='178' height='50' align='center' bgcolor='#f4f8fb'>GBP<strong class='red'>利空</strong></td>\n\
                                <td width='166' height='50' align='center' bgcolor='#f4f8fb'>NZD<strong class='green'>利多</strong></td></tr>";
						$("#rili_tab03").append(newRow);
					}
				}
                            }else{
                                
                                if (typeof(res.length)=='undefined') {
                                	for(var j=0;j<country.length;j++){
                                if(country[j] == res["fdCountry"]){
				var newRow = "<tr>\n\
                                <td width='129' align='left' class='number1'><span>"+res["fdTime"]+"</span><span style='padding-left:20px;'>"+rebackcounty(res["fdCountry"])+"</span></td>\n\
                                <td width='191' height='50' align='left' bgcolor='#f4f8fb'>"+res["fdTitle"]+"</td>\n\
                                <td width='85' height='50' align='center' bgcolor='#f4f8fb' class='star'>"+importantLevel(res["importance"])+"</td>\n\
                                <td width='80' height='50' align='center' bgcolor='#f4f8fb'>"+res["lastValue"]+"</td>\n\
                                <td width='94' height='50' align='center' bgcolor='#f4f8fb'>"+res["prediction"]+"</td>\n\
                                <td width='88' height='50' align='center' bgcolor='#f4f8fb'>"+res["actual"]+"</td>\n\
                                <td width='178' height='50' align='center' bgcolor='#f4f8fb'>GBP<strong class='red'>利空</strong></td>\n\
                                <td width='166' height='50' align='center' bgcolor='#f4f8fb'>NZD<strong class='green'>利多</strong></td></tr>";
				$("#rili_tab03").append(newRow);}}
				}else{
					for(var i=0;i<res.length;i++){
						for(var j=0;j<country.length;j++){
                                            if(country[j] == res[i]["fdCountry"]){
						var newRow = "<tr>\n\
                                <td width='129' align='left' class='number1'><span>"+res[i]["fdTime"]+"</span><span style='padding-left:20px;'>"+rebackcounty(res[i]["fdCountry"])+"</span></td>\n\
                                <td width='191' height='50' align='left' bgcolor='#f4f8fb'>"+res[i]["fdTitle"]+"</td>\n\
                                <td width='85' height='50' align='center' bgcolor='#f4f8fb' class='star'>"+importantLevel(res[i]["importance"])+"</td>\n\
                                <td width='80' height='50' align='center' bgcolor='#f4f8fb'>"+res[i]["lastValue"]+"</td>\n\
                                <td width='94' height='50' align='center' bgcolor='#f4f8fb'>"+res[i]["prediction"]+"</td>\n\
                                <td width='88' height='50' align='center' bgcolor='#f4f8fb'>"+res[i]["actual"]+"</td>\n\
                                <td width='178' height='50' align='center' bgcolor='#f4f8fb'>GBP<strong class='red'>利空</strong></td>\n\
                                <td width='166' height='50' align='center' bgcolor='#f4f8fb'>NZD<strong class='green'>利多</strong></td></tr>";
						$("#rili_tab03").append(newRow);
                                            }}
					}
				}
                            }
			}
			else{
				var newRow ="<tr><td>"+'今日无重要数据公布！'+"</td><td></td><td></td></tr>";
				$("#rili_tab03").append(newRow);
			}
			sethash(res.length+rea.length+ret.length+3);
		},'json');
	return false;
}
function  rebackcounty(currencyType){
        if("新西兰"==currencyType){
			return "<img src='../../cn/images/cf_news/NewZealand.jpg' />";
		}else if("韩国"==currencyType){
			return "<img src='../../cn/images/cf_news/Korea.jpg' />";
		}else if("澳大利亚"==currencyType){
			return "<img src='../../cn/images/cf_news/Australian.jpg' />";
		}else if("日本"==currencyType){
			return "<img src='../../cn/images/cf_news/japan.jpg' />";
		}else if("德国"==currencyType){
			return "<img src='../../cn/images/cf_news/Germany.jpg' />";
		}else if("瑞士"==currencyType){
			return "<img src='../../cn/images/cf_news/Switzerland.jpg' />";
		}else if("香港"==currencyType){
			return "<img src='../../cn/images/cf_news/hongkong.jpg' />";
		}else if("西班牙"==currencyType){
			return "<img src='../../cn/images/cf_news/spain.jpg' />";
		}else if("英国"==currencyType){
			return "<img src='../../cn/images/cf_news/UK.jpg' />";
		}else if("意大利"==currencyType){
			return "<img src='../../cn/images/cf_news/Italy.jpg' />";
		}else if("加拿大"==currencyType){
			return "<img src='../../cn/images/cf_news/Canada.jpg' />";
		}else if("美国"==currencyType){
			return "<img src='../../cn/images/cf_news/usa.jpg' />";
		}else if("中国"==currencyType){
			return "<img src='../../cn/images/cf_news/china.jpg' />";
		}else if("台湾"==currencyType){
			return "<img src='../../cn/images/cf_news/Taiwan.jpg' />";
		}else if("法国"==currencyType){
			return "<img src='../../cn/images/cf_news/France.jpg' />";
		}else if("欧元区"==currencyType){
			return "<img src='../../cn/images/cf_news/EuropeanUnion.jpg' />";
		}else if("南非"==currencyType){
			return "<img src='../../cn/images/cf_news/SouthAfrica.jpg' />";
		}else if("巴西"==currencyType){
			return "<img src='../../cn/images/cf_news/brazil.jpg' />";
		}else if("印度"==currencyType){
			return "<img src='../../cn/images/cf_news/India.jpg' />";
		}else if("希腊"==currencyType){
			return "<img src='../../cn/images/cf_news/Greece.jpg' />";
		}else if("新加坡"==currencyType){
			return "<img src='../../cn/images/cf_news/Singapore.jpg' />";
		}else if("奥地利"==currencyType){
			return "<img src='../../cn/images/cf_news/Austria.jpg' />";
		}else if("OECD"==currencyType){
			return "none";
		} //Greece 希腊  CNH 离岸人民币  Portugal葡萄牙   
		else{
			return currencyType;
		}
}
function importantLevel(level){
    if(level == "低"){
        return "<i>★</i>★★★★";
    }else if(level == "中"){
        return "<i>★</i><i>★</i><i>★</i>★★";
    }else if(level == "高"){
        return "<i>★</i><i>★</i><i>★</i><i>★</i><i>★</i>";
    }else{
        return "<i>★</i><i>★</i><i>★</i><i>★</i><i>★</i>";
    }
}





