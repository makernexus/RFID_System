// checkinreports.js
//
// Creative Commons: Attribution/Share Alike/Non Commercial (cc) 2022 Maker Nexus
// By Jim Schrempp
//
//  Jan 2023:
//      Changed range on weekly graph to start at 20220101 and end 20231221
//  Nov 2022: 
//      Moved JS from the .txt to this file. 


// ----------------
// ONLOAD
window.onload = function() {
    //dom not only ready, but everything is loaded
    graphdata1 = makeGraphData('graph1dataX', 'graph1dataY');
    var graphlayout1 = {
        autosize: false,
        width: 400,
        height: 350,
        title: {
            text:'Unique Members Visiting',
            font: {
                size: 16
            },
            xref: 'paper',
            x: 0.05,
        }
    };

    gdiv1 = document.getElementById('graph1');
    Plotly.newPlot(gdiv1,graphdata1,graphlayout1,{responsive: true});

    var rangeStart = '2022-01-01';
    var rangeEnd = '2023-12-31';
    graphdata2 = makeGraphData('graph2dataX', 'graph2dataY' );
    var graphlayout2 = {
        autosize: false,
        width: 600,
        height: 350,
        title: {
            text:'Unique Members Visiting per day',
            font: {
                size: 16
            },
            xref: 'paper',
            x: 0.05
        },               
        xaxis: {
            autorange: false,
            tickangle: '90',
            range: [ rangeStart, rangeEnd ],
            tick0: rangeStart,
            dtick: 'M3'
        }
        
    };

    gdiv2 = document.getElementById('graph2');
    Plotly.newPlot(gdiv2,graphdata2,graphlayout2,{responsive: true});
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



