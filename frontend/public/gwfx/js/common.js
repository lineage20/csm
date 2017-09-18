function playVideo(x, no) {
	// 播放器
	jwplayer("container").setup( {
		file : x,
		width : "659",
		height : "370",
		// image: "",
		// screencolor:"#fff",
		autostart : true
	});
}

// 优酷视频播放
function youkuVideo(x, no) {
	// <div class='player_html5'><div style='height:100%' class='picture'><div
	// style=''line-height:460px;'>"
	// "<span style='font-size:18px'>您还没有安装flash播放器,请点击<a target='_blank'
	// href='http://www.adobe.com/go/getflash'>这里</a>安装</span></div></div></div>
	// 播放器
	/*
	 * var youVideo = "<object width='100%' height='100%' id='movie_player'
	 * data='http://static.youku.com/v1.0.0496/v/swf/loader.swf'"
	 * "type='application/x-shockwave-flash'><param value='true'
	 * name='allowFullScreen'><param value='always' name='allowscriptaccess'>" "<param
	 * value='VideoIDS=XODY1OTMzMzEy&amp;ShowId=281013&amp;category=91&amp;Cp=authorized&amp;ev=2&amp;Light=on&amp;THX=off&amp;unCookie=0&amp;frame=0&amp;pvid=1420615464525eMCgjv&amp;uepflag=0&amp;Tid=0&amp;isAutoPlay=true&amp;Version=/v1.0.1015&amp;show_ce=0&amp;winType=interior&amp;embedid=AjIxNjQ4MzMyOAJ2LnlvdWt1LmNvbQIvdl9zaG93L2lkX1hPRFkxT1RNek16RXlfZXZfMi5odG1s&amp;vext=bc%3D%26pid%3D1420615464525eMCgjv%26unCookie%3D0%26frame%3D0%26type%3D0%26svt%3D1%26stg%3D261%26emb%3DAjIxNjQ4MzMyOAJ2LnlvdWt1LmNvbQIvdl9zaG93L2lkX1hPRFkxT1RNek16RXlfZXZfMi5odG1s%26dn%3D%E7%BD%91%E9%A1%B5%26hwc%3D1%26mtype%3Doth'"
	 * "name='flashvars'><param
	 * value='http://static.youku.com/v1.0.0496/v/swf/loader.swf' name='movie'>" "</object>";
	 */

	/*
	 * var youVideo = "<embed src='"+x+"' quality='high' width='100%'
	 * height='100%' allowFullScreen='true' mode='transparent'" "align='middle'
	 * allowScriptAccess='always' type='application/x-shockwave-flash'></embed>";
	 */
	var youVideo = "<embed src='"
			+ x
			+ "' quality='high' width='659' height='379' allowFullScreen='true' mode='transparent' align='middle' allowScriptAccess='always' type='application/x-shockwave-flash'></embed>";
	$("#container").html(youVideo);

}