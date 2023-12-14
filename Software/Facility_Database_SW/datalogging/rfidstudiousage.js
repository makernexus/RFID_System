// rfidstudiousage.js
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2022 Maker Nexus
// By Jim Schrempp
//
// Dec 2023:
//     added 3 more graphs 
//     increased the y-axis range to 30
// Nov 2022: 
//      Moved JS from the .txt to this file. 


var g_rangeStart;
var g_rangeEnd;

function setGraphRange(start, end) {
    g_rangeStart = start;
    g_rangeEnd = end;
}

// ----------------
// ONLOAD
window.onload = function() {

        // start and end of the x-axis
        var rangeStart = g_rangeStart;
        var rangeEnd = g_rangeEnd;

        // make graph 1, electronics
		graphdata1 = makeGraphData('graphelectronicsdataX','graphelectronicsdataY');
        graphlayout1 = makeGraphLayout( 'Electronics, visits per week',rangeStart, rangeEnd);
		gdiv1 = document.getElementById('graphelectronics');
		Plotly.newPlot(gdiv1,graphdata1,graphlayout1,{responsive: true});
		
        // make graph 2, textiles
		graphdata2 = makeGraphData('graphtextiledataX','graphtextiledataY');
		graphlayout2 = makeGraphLayout( 'Textiles, visits per week',rangeStart, rangeEnd);
		gdiv2 = document.getElementById('graphtextile');
		Plotly.newPlot(gdiv2,graphdata2,graphlayout2,{responsive: true});

        // make graph 3, woodshop
		graphdata3 = makeGraphData('graphwoodshopdataX','graphwoodshopdataY');
		graphlayout3 = makeGraphLayout( 'Woodshop, visits per week',rangeStart, rangeEnd);
		gdiv3 = document.getElementById('graphwoodshop');
		Plotly.newPlot(gdiv3,graphdata3,graphlayout3,{responsive: true});

        // make graph 4, woodshop
		graphdata4 = makeGraphData('graphhotshopdataX','graphhotshopdataY');
		graphlayout4 = makeGraphLayout( 'Hot Shop, visits per week',rangeStart, rangeEnd);
		gdiv4 = document.getElementById('graphhotshop');
		Plotly.newPlot(gdiv4,graphdata4,graphlayout4,{responsive: true});

        // make graph 5, woodshop
		graphdata5 = makeGraphData('graphcoldshopdataX','graphcoldshopdataY');
		graphlayout5 = makeGraphLayout( 'Cold Shop, visits per week',rangeStart, rangeEnd);
		gdiv5 = document.getElementById('graphcoldshop');
		Plotly.newPlot(gdiv5,graphdata5,graphlayout5,{responsive: true});

        // make graph 6, woodshop
		graphdata6 = makeGraphData('graph3ddataX','graph3ddataY');
		graphlayout6 = makeGraphLayout( '3D Printers, visits per week',rangeStart, rangeEnd);
		gdiv6 = document.getElementById('graph3d');
		Plotly.newPlot(gdiv6,graphdata6,graphlayout6,{responsive: true});
}

// ------------
// customize the graph layout 
//   pass in title and x-axis range
function makeGraphLayout(title, rangeStart, rangeEnd) {
    var graphLayout = {
        autosize: false,
        width: 600,
        height: 300,
        title: {
            text:title,
            font: {
                // family: 'Courier New, monospace',
                size: 16
                },
            xref: 'paper',
            x: 0.05,
            },
        xaxis: {
            autorange: false,
            tickangle: '-90',
            range: [ rangeStart, rangeEnd ]
        },
        yaxis: {
            autorange: false,
            range: [0, 30]
        }
    };
    return graphLayout;
}

// --------------
// create the data object for a graph
//   pass in the document elements that hold the values, | delimited
//
function makeGraphData(elementIDX, elementIDY){

    dataX = document.getElementById(elementIDX).textContent;
    dataY = document.getElementById(elementIDY).textContent;
    var x = dataX.split("|");
    var y = dataY.split("|").map(Number);
    var graphData = [
        {
            x:x, 
            y:y, 
            type:"bar"
        }
    ];
    return graphData;

}
