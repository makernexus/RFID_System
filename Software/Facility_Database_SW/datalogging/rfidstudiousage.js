// rfidstudiousage.js
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2022 Maker Nexus
// By Jim Schrempp
//
//  Nov 2022: 
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
            range: [0, 20]
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
