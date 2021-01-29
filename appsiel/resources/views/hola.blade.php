<!DOCTYPE html>
<!-- (C) 2013-present  SheetJS http://sheetjs.com -->
<!-- vim: set ts=2: -->
<html>

<head>
    <title>SheetJS JS-XLSX In-Browser HTML Table Export Demo</title>
    <meta charset="utf-8" />
    <style>
        .xport,
        .btn {
            display: inline;
            text-align: center;
        }

        a {
            text-decoration: none
        }

        #data-table,
        #data-table th,
        #data-table td {
            border: 1px solid black
        }
    </style>
</head>

<body>
    <![if gt IE 9]>
    <script type="text/javascript" src="//unpkg.com/xlsx/dist/shim.min.js"></script>
    <script type="text/javascript" src="//unpkg.com/xlsx/dist/xlsx.full.min.js"></script>

    <script type="text/javascript" src="//unpkg.com/blob.js@1.0.1/Blob.js"></script>
    <script type="text/javascript" src="//unpkg.com/file-saver@1.3.3/FileSaver.js"></script>
    <![endif]>

    <!--[if lte IE 9]>
<script type="text/javascript" src="shim.min.js"></script>
<script type="text/javascript" src="xlsx.full.min.js"></script>

<script type="text/javascript" src="Blob.js"></script>
<script type="text/javascript" src="FileSaver.js"></script>
<![endif]-->

    <!--[if lte IE 9]>
<script type="text/javascript" src="swfobject.js"></script>
<script type="text/javascript" src="downloadify.min.js"></script>
<script type="text/javascript" src="base64.min.js"></script>
<![endif]-->

    <script>
        function doit(type, fn, dl) {
            var elt = document.getElementById('data-table');
            var wb = XLSX.utils.table_to_book(elt, {
                sheet: "Sheet JS"
            });
            return dl ?
                XLSX.write(wb, {
                    bookType: type,
                    bookSST: true,
                    type: 'base64'
                }) :
                XLSX.writeFile(wb, fn || ('SheetJSTableExport.' + (type || 'xlsx')));
        }
    </script>
    <pre><h3><a href="//sheetjs.com/">SheetJS</a> JS-XLSX In-Browser HTML Table Export Demo</h3>
<b>Compatibility notes:</b>
- Editable table leverages the HTML5 contenteditable feature, supported in most browsers.
- IE6-9 requires ActiveX or Flash to download files.
- iOS Safari file download may not work. <a href="https://git.io/ios_save">This is a known issue</a>.
- This build is comprehensive. <a href="//sheetjs.com/demos/tablemini">The "mini" build only includes XLSX support</a>.

<b>Editable Data Table:</b> (click a cell to edit it)
</pre>
    <div id="container"></div>
    <script type="text/javascript">
        /* initial table */
        var aoa = [
            ["This", "is", "a", "Test"],
            ["வணக்கம்", "สวัสดี", "你好", "가지마"],
            [1, 2, 3, 4],
            ["Click", "to", "edit", "cells"]
        ];
        var ws = XLSX.utils.aoa_to_sheet(aoa);
        var html_string = XLSX.utils.sheet_to_html(ws, {
            id: "data-table",
            editable: true
        });
        document.getElementById("container").innerHTML = html_string;
    </script>
    <br />
    <pre><b>Export it!</b></pre>
    <table id="xport">
        <tr>
            <td>
                <pre>XLSX Excel 2007+ XML</pre>
            </td>
            <td>
                <p id="xportxlsx" class="xport"><input type="submit" value="Export to XLSX!" onclick="doit('xlsx');"></p>
                <p id="xlsxbtn" class="btn">Flash required for actually downloading the generated file.</p>
            </td>
        </tr>
        <tr>
            <td>
                <pre>XLSB Excel 2007+ Binary</pre>
            </td>
            <td>
                <p id="xportxlsb" class="xport"><input type="submit" value="Export to XLSB!" onclick="doit('xlsb');"></p>
                <p id="xlsbbtn" class="btn">Flash required for actually downloading the generated file.</p>
            </td>
        </tr>
        <tr>
            <td>
                <pre>XLS Excel 97-2004 Binary</pre>
            </td>
            <td>
                <p id="xportbiff8" class="xport"><input type="submit" value="Export to XLS!" onclick="doit('biff8', 'SheetJSTableExport.xls');"></p>
                <p id="biff8btn" class="btn">Flash required for actually downloading the generated file.</p>
            </td>
        </tr>
        <tr>
            <td>
                <pre>ODS</pre>
            </td>
            <td>
                <p id="xportods" class="xport"><input type="submit" value="Export to ODS!" onclick="doit('ods');"></p>
                <p id="odsbtn" class="btn">Flash required for actually downloading the generated file.</p>
            </td>
        </tr>
        <tr>
            <td>
                <pre>Flat ODS</pre>
            </td>
            <td>
                <p id="xportfods" class="xport"><input type="submit" value="Export to FODS!" onclick="doit('fods', 'SheetJSTableExport.fods');"></p>
                <p id="fodsbtn" class="btn">Flash required for actually downloading the generated file.</p>
            </td>
        </tr>
        <tr>
            <td>
                <pre>SpreadsheetML 2003</pre>
            </td>
            <td>
                <p id="xportxlml" class="xport"><input type="submit" value="Export to XLML!" onclick="doit('xlml', 'SheetJSTableExport.xml');"></p>
                <p id="xlmlbtn" class="btn">Flash required for actually downloading the generated file.</p>
            </td>
        </tr>
    </table>
    <pre><b>Powered by the <a href="//sheetjs.com/opensource">community version of sheetjs</a></b></pre>
    <script type="text/javascript">
        function tableau(pid, iid, fmt, ofile) {
            if (typeof Downloadify !== 'undefined') Downloadify.create(pid, {
                swf: 'downloadify.swf',
                downloadImage: 'download.png',
                width: 100,
                height: 30,
                filename: ofile,
                data: function() {
                    return doit(fmt, ofile, true);
                },
                transparent: false,
                append: false,
                dataType: 'base64',
                onComplete: function() {
                    alert('Your File Has Been Saved!');
                },
                onCancel: function() {
                    alert('You have cancelled the saving of this file.');
                },
                onError: function() {
                    alert('You must put something in the File Contents or there will be nothing to save!');
                }
            });
            else document.getElementById(pid).innerHTML = "";
        }
        tableau('biff8btn', 'xportbiff8', 'biff8', 'SheetJSTableExport.xls');
        tableau('odsbtn', 'xportods', 'ods', 'SheetJSTableExport.ods');
        tableau('fodsbtn', 'xportfods', 'fods', 'SheetJSTableExport.fods');
        tableau('xlmlbtn', 'xportxlml', 'xlml', 'SheetJSTableExport.xml');
        tableau('xlsbbtn', 'xportxlsb', 'xlsb', 'SheetJSTableExport.xlsb');
        tableau('xlsxbtn', 'xportxlsx', 'xlsx', 'SheetJSTableExport.xlsx');
    </script>
    <script type="text/javascript">
        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', 'UA-36810333-1']);
        _gaq.push(['_setDomainName', 'sheetjs.com']);
        _gaq.push(['_setAllowLinker', true]);
        _gaq.push(['_trackPageview']);

        (function() {
            var ga = document.createElement('script');
            ga.type = 'text/javascript';
            ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0];
            s.parentNode.insertBefore(ga, s);
        })();
    </script>
</body>

</html>