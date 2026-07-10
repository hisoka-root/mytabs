/*
 *	MyTabs - Tabbed forum browsing plugin for MyBB
 *
 *	Copyright (C) 2026  hisoka (root@hisoka.lol)
 *	https://github.com/hisoka-root
 *
 *	Originally created by Ethan / FatalMessiah at MyBBPlugins
 *	http://www.mybbplug.in/s/
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

function switchTab(tab, ajax)
{
	if(ajax)
	{
		/* AJAX Code -- Start */
		getPage('index.php?tab=' + tab + '&output-mytab-code=1');
		/* AJAX Code -- End */
	}
	else
	{
		/* Non-AJAX Code -- Start */
		var nav_list = document.getElementById('tab_nav').children;
		for(var n = 0; n < nav_list.length; n++)
		{
			if(nav_list[n].id == ("tab_nav_" + tab))
			{
				nav_list[n].style.display = "";
			}
			else
			{
				nav_list[n].style.display = "none";
			}
		}

		var content = document.getElementById('tab_content').children;
		for(var c = 0; c < content.length; c++)
		{
			if(content[c].id == ("tab_" + tab))
			{
				content[c].style.display = "";
			}
			else
			{
				content[c].style.display = "none";
			}
		}
		/* Non-AJAX Code -- End */
	}
	return false;
}

function getPage(url)
{
	var req;
	try
	{
		req = new XMLHttpRequest();
	}
	catch(e)
	{
		return true;
	}
	req.onreadystatechange = function()
	{
		if((req.readyState == 4) && (req.status == 200) && req.responseText.length > 0)
		{
			document.getElementById('mytabs_full').innerHTML = req.responseText;
		}
	};
	req.open("GET", url, true);
	req.send(null);
}
