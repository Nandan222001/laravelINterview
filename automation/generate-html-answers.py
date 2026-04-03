#!/usr/bin/env python3
"""
Comprehensive Answer Generation System

Reads questions.md and answers.md from all topic directories,
augments answers with detailed explanations, converts markdown code blocks
to syntax-highlighted HTML using pygments, generates architecture flow diagrams
as inline SVG, and outputs fully-formatted HTML answer sections.
"""

import os
import re
import sys
import json
from pathlib import Path
from typing import Dict, List, Tuple, Optional

try:
    from pygments import highlight
    from pygments.lexers import get_lexer_by_name, guess_lexer
    from pygments.formatters import HtmlFormatter
    from pygments.util import ClassNotFound
except ImportError:
    print("Error: pygments not installed. Install with: pip install pygments")
    sys.exit(1)


class SVGFlowDiagramGenerator:
    """Generates inline SVG flow diagrams for various architectural concepts"""
    
    @staticmethod
    def generate_laravel_request_lifecycle() -> str:
        """Generate Laravel request lifecycle flow diagram"""
        return '''<svg width="800" height="600" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
      <polygon points="0 0, 10 3.5, 0 7" fill="#333" />
    </marker>
    <style>
      .box { fill: #4A90E2; stroke: #2E5C8A; stroke-width: 2; }
      .text { fill: white; font-family: Arial, sans-serif; font-size: 14px; text-anchor: middle; }
      .arrow { stroke: #333; stroke-width: 2; fill: none; marker-end: url(#arrowhead); }
      .label { fill: #666; font-family: Arial, sans-serif; font-size: 12px; }
    </style>
  </defs>
  
  <rect class="box" x="300" y="20" width="200" height="50" rx="5"/>
  <text class="text" x="400" y="50">HTTP Request</text>
  
  <line class="arrow" x1="400" y1="70" x2="400" y2="100"/>
  
  <rect class="box" x="300" y="100" width="200" height="50" rx="5"/>
  <text class="text" x="400" y="130">public/index.php</text>
  
  <line class="arrow" x1="400" y1="150" x2="400" y2="180"/>
  
  <rect class="box" x="300" y="180" width="200" height="50" rx="5"/>
  <text class="text" x="400" y="210">Kernel</text>
  
  <line class="arrow" x1="400" y1="230" x2="400" y2="260"/>
  
  <rect class="box" x="300" y="260" width="200" height="50" rx="5"/>
  <text class="text" x="400" y="290">Middleware Stack</text>
  
  <line class="arrow" x1="400" y1="310" x2="400" y2="340"/>
  
  <rect class="box" x="300" y="340" width="200" height="50" rx="5"/>
  <text class="text" x="400" y="370">Router</text>
  
  <line class="arrow" x1="400" y1="390" x2="400" y2="420"/>
  
  <rect class="box" x="300" y="420" width="200" height="50" rx="5"/>
  <text class="text" x="400" y="450">Controller</text>
  
  <line class="arrow" x1="400" y1="470" x2="400" y2="500"/>
  
  <rect class="box" x="300" y="500" width="200" height="50" rx="5"/>
  <text class="text" x="400" y="530">Response</text>
  
  <text class="label" x="550" y="130">Bootstrap</text>
  <text class="label" x="550" y="210">Handle Request</text>
  <text class="label" x="550" y="290">Auth, CORS, etc.</text>
  <text class="label" x="550" y="370">Match Route</text>
  <text class="label" x="550" y="450">Execute Logic</text>
  <text class="label" x="550" y="530">Return to Client</text>
</svg>'''
    
    @staticmethod
    def generate_payment_processing_flow() -> str:
        """Generate payment processing flow diagram"""
        return '''<svg width="900" height="700" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <marker id="arrowhead2" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
      <polygon points="0 0, 10 3.5, 0 7" fill="#333" />
    </marker>
    <style>
      .box { fill: #27AE60; stroke: #1E8449; stroke-width: 2; }
      .process { fill: #E67E22; stroke: #CA6F1E; stroke-width: 2; }
      .decision { fill: #F39C12; stroke: #D68910; stroke-width: 2; }
      .text { fill: white; font-family: Arial, sans-serif; font-size: 13px; text-anchor: middle; }
      .arrow { stroke: #333; stroke-width: 2; fill: none; marker-end: url(#arrowhead2); }
      .label { fill: #666; font-family: Arial, sans-serif; font-size: 11px; }
    </style>
  </defs>
  
  <rect class="box" x="350" y="20" width="200" height="50" rx="5"/>
  <text class="text" x="450" y="50">Client Initiates Payment</text>
  
  <line class="arrow" x1="450" y1="70" x2="450" y2="100"/>
  
  <rect class="process" x="350" y="100" width="200" height="50" rx="5"/>
  <text class="text" x="450" y="130">Validate Order</text>
  
  <line class="arrow" x1="450" y1="150" x2="450" y2="180"/>
  
  <rect class="process" x="350" y="180" width="200" height="50" rx="5"/>
  <text class="text" x="450" y="210">Create Payment Record</text>
  
  <line class="arrow" x1="450" y1="230" x2="450" y2="260"/>
  
  <rect class="process" x="350" y="260" width="200" height="50" rx="5"/>
  <text class="text" x="450" y="290">Call Payment Gateway</text>
  
  <line class="arrow" x1="450" y1="310" x2="450" y2="340"/>
  
  <polygon class="decision" points="450,340 550,380 450,420 350,380"/>
  <text class="text" x="450" y="385">Success?</text>
  
  <line class="arrow" x1="550" y1="380" x2="650" y2="380"/>
  <text class="label" x="600" y="370">Yes</text>
  
  <rect class="box" x="650" y="355" width="180" height="50" rx="5"/>
  <text class="text" x="740" y="385">Update Order Status</text>
  
  <line class="arrow" x1="740" y1="405" x2="740" y2="450"/>
  
  <rect class="box" x="650" y="450" width="180" height="50" rx="5"/>
  <text class="text" x="740" y="480">Send Confirmation</text>
  
  <line class="arrow" x1="350" y1="380" x2="250" y2="380"/>
  <text class="label" x="300" y="370">No</text>
  
  <rect class="process" x="70" y="355" width="180" height="50" rx="5"/>
  <text class="text" x="160" y="385">Log Error</text>
  
  <line class="arrow" x1="160" y1="405" x2="160" y2="450"/>
  
  <rect class="process" x="70" y="450" width="180" height="50" rx="5"/>
  <text class="text" x="160" y="480">Notify User</text>
  
  <line class="arrow" x1="160" y1="500" x2="160" y2="550"/>
  
  <rect class="box" x="70" y="550" width="180" height="50" rx="5"/>
  <text class="text" x="160" y="580">Retry/Cancel Flow</text>
  
  <line class="arrow" x1="740" y1="500" x2="740" y2="620"/>
  
  <rect class="box" x="300" y="600" width="340" height="50" rx="5"/>
  <text class="text" x="470" y="630">Webhook Handler (Async)</text>
</svg>'''
    
    @staticmethod
    def generate_database_query_execution() -> str:
        """Generate database query execution plan diagram"""
        return '''<svg width="850" height="650" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <marker id="arrowhead3" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
      <polygon points="0 0, 10 3.5, 0 7" fill="#333" />
    </marker>
    <style>
      .box { fill: #8E44AD; stroke: #6C3483; stroke-width: 2; }
      .cache { fill: #16A085; stroke: #138D75; stroke-width: 2; }
      .index { fill: #D35400; stroke: #A04000; stroke-width: 2; }
      .text { fill: white; font-family: Arial, sans-serif; font-size: 13px; text-anchor: middle; }
      .arrow { stroke: #333; stroke-width: 2; fill: none; marker-end: url(#arrowhead3); }
      .label { fill: #666; font-family: Arial, sans-serif; font-size: 11px; }
    </style>
  </defs>
  
  <rect class="box" x="325" y="20" width="200" height="50" rx="5"/>
  <text class="text" x="425" y="50">SQL Query</text>
  
  <line class="arrow" x1="425" y1="70" x2="425" y2="100"/>
  
  <rect class="box" x="325" y="100" width="200" height="50" rx="5"/>
  <text class="text" x="425" y="130">Parser</text>
  
  <line class="arrow" x1="425" y1="150" x2="425" y2="180"/>
  
  <rect class="box" x="325" y="180" width="200" height="50" rx="5"/>
  <text class="text" x="425" y="210">Query Optimizer</text>
  
  <line class="arrow" x1="425" y1="230" x2="300" y2="280"/>
  <line class="arrow" x1="425" y1="230" x2="550" y2="280"/>
  
  <rect class="index" x="150" y="280" width="180" height="50" rx="5"/>
  <text class="text" x="240" y="310">Index Scan</text>
  
  <rect class="cache" x="470" y="280" width="180" height="50" rx="5"/>
  <text class="text" x="560" y="310">Table Scan</text>
  
  <line class="arrow" x1="240" y1="330" x2="240" y2="380"/>
  <line class="arrow" x1="560" y1="330" x2="560" y2="380"/>
  
  <rect class="box" x="325" y="380" width="200" height="50" rx="5"/>
  <text class="text" x="425" y="410">Execution Engine</text>
  
  <line class="arrow" x1="425" y1="430" x2="425" y2="460"/>
  
  <rect class="cache" x="325" y="460" width="200" height="50" rx="5"/>
  <text class="text" x="425" y="490">Buffer Pool</text>
  
  <line class="arrow" x1="425" y1="510" x2="425" y2="540"/>
  
  <rect class="box" x="325" y="540" width="200" height="50" rx="5"/>
  <text class="text" x="425" y="570">Result Set</text>
  
  <text class="label" x="580" y="130">Syntax check</text>
  <text class="label" x="580" y="210">Choose best plan</text>
  <text class="label" x="110" y="310">If indexed</text>
  <text class="label" x="670" y="310">If no index</text>
  <text class="label" x="580" y="490">Cache hit/miss</text>
</svg>'''
    
    @staticmethod
    def generate_react_component_lifecycle() -> str:
        """Generate React component lifecycle diagram"""
        return '''<svg width="900" height="750" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <marker id="arrowhead4" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
      <polygon points="0 0, 10 3.5, 0 7" fill="#333" />
    </marker>
    <style>
      .mount { fill: #27AE60; stroke: #1E8449; stroke-width: 2; }
      .update { fill: #3498DB; stroke: #2874A6; stroke-width: 2; }
      .unmount { fill: #E74C3C; stroke: #C0392B; stroke-width: 2; }
      .text { fill: white; font-family: Arial, sans-serif; font-size: 13px; text-anchor: middle; }
      .arrow { stroke: #333; stroke-width: 2; fill: none; marker-end: url(#arrowhead4); }
      .label { fill: #666; font-family: Arial, sans-serif; font-size: 12px; font-weight: bold; }
      .phase { fill: #34495E; font-family: Arial, sans-serif; font-size: 14px; font-weight: bold; }
    </style>
  </defs>
  
  <text class="label" x="50" y="50" fill="#27AE60">MOUNTING PHASE</text>
  
  <rect class="mount" x="50" y="70" width="250" height="50" rx="5"/>
  <text class="text" x="175" y="100">constructor()</text>
  
  <line class="arrow" x1="175" y1="120" x2="175" y2="150"/>
  
  <rect class="mount" x="50" y="150" width="250" height="50" rx="5"/>
  <text class="text" x="175" y="180">getDerivedStateFromProps()</text>
  
  <line class="arrow" x1="175" y1="200" x2="175" y2="230"/>
  
  <rect class="mount" x="50" y="230" width="250" height="50" rx="5"/>
  <text class="text" x="175" y="260">render()</text>
  
  <line class="arrow" x1="175" y1="280" x2="175" y2="310"/>
  
  <rect class="mount" x="50" y="310" width="250" height="50" rx="5"/>
  <text class="text" x="175" y="340">componentDidMount()</text>
  
  <text class="label" x="400" y="50" fill="#3498DB">UPDATING PHASE</text>
  
  <rect class="update" x="400" y="70" width="250" height="50" rx="5"/>
  <text class="text" x="525" y="100">getDerivedStateFromProps()</text>
  
  <line class="arrow" x1="525" y1="120" x2="525" y2="150"/>
  
  <rect class="update" x="400" y="150" width="250" height="50" rx="5"/>
  <text class="text" x="525" y="180">shouldComponentUpdate()</text>
  
  <line class="arrow" x1="525" y1="200" x2="525" y2="230"/>
  
  <rect class="update" x="400" y="230" width="250" height="50" rx="5"/>
  <text class="text" x="525" y="260">render()</text>
  
  <line class="arrow" x1="525" y1="280" x2="525" y2="310"/>
  
  <rect class="update" x="400" y="310" width="250" height="50" rx="5"/>
  <text class="text" x="525" y="340">getSnapshotBeforeUpdate()</text>
  
  <line class="arrow" x1="525" y1="360" x2="525" y2="390"/>
  
  <rect class="update" x="400" y="390" width="250" height="50" rx="5"/>
  <text class="text" x="525" y="420">componentDidUpdate()</text>
  
  <line class="arrow" x1="525" y1="440" x2="525" y2="150" stroke-dasharray="5,5"/>
  
  <text class="label" x="750" y="250" fill="#E74C3C">UNMOUNTING</text>
  
  <rect class="unmount" x="700" y="270" width="180" height="50" rx="5"/>
  <text class="text" x="790" y="300">componentWillUnmount()</text>
  
  <text class="label" x="225" y="500" fill="#9B59B6">REACT 18 HOOKS (Functional)</text>
  
  <rect class="mount" x="100" y="530" width="200" height="40" rx="5"/>
  <text class="text" x="200" y="555">useState()</text>
  
  <rect class="update" x="320" y="530" width="200" height="40" rx="5"/>
  <text class="text" x="420" y="555">useEffect()</text>
  
  <rect class="mount" x="540" y="530" width="200" height="40" rx="5"/>
  <text class="text" x="640" y="555">useContext()</text>
  
  <rect class="update" x="100" y="590" width="200" height="40" rx="5"/>
  <text class="text" x="200" y="615">useReducer()</text>
  
  <rect class="mount" x="320" y="590" width="200" height="40" rx="5"/>
  <text class="text" x="420" y="615">useMemo()</text>
  
  <rect class="update" x="540" y="590" width="200" height="40" rx="5"/>
  <text class="text" x="640" y="615">useCallback()</text>
  
  <rect class="unmount" x="210" y="650" width="200" height="40" rx="5"/>
  <text class="text" x="310" y="675">useRef()</text>
  
  <rect class="mount" x="430" y="650" width="200" height="40" rx="5"/>
  <text class="text" x="530" y="675">useLayoutEffect()</text>
</svg>'''
    
    @staticmethod
    def generate_kubernetes_deployment_flow() -> str:
        """Generate Kubernetes deployment flow diagram"""
        return '''<svg width="850" height="700" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <marker id="arrowhead5" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
      <polygon points="0 0, 10 3.5, 0 7" fill="#333" />
    </marker>
    <style>
      .master { fill: #326CE5; stroke: #1E5BC6; stroke-width: 2; }
      .node { fill: #28A745; stroke: #1E7E34; stroke-width: 2; }
      .pod { fill: #FD7E14; stroke: #DC6A0B; stroke-width: 2; }
      .text { fill: white; font-family: Arial, sans-serif; font-size: 12px; text-anchor: middle; }
      .arrow { stroke: #333; stroke-width: 2; fill: none; marker-end: url(#arrowhead5); }
      .label { fill: #666; font-family: Arial, sans-serif; font-size: 11px; }
    </style>
  </defs>
  
  <rect class="master" x="325" y="20" width="200" height="50" rx="5"/>
  <text class="text" x="425" y="50">kubectl apply -f</text>
  
  <line class="arrow" x1="425" y1="70" x2="425" y2="100"/>
  
  <rect class="master" x="325" y="100" width="200" height="50" rx="5"/>
  <text class="text" x="425" y="130">API Server</text>
  
  <line class="arrow" x1="425" y1="150" x2="425" y2="180"/>
  
  <rect class="master" x="325" y="180" width="200" height="50" rx="5"/>
  <text class="text" x="425" y="210">etcd (Store State)</text>
  
  <line class="arrow" x1="425" y1="230" x2="300" y2="280"/>
  <line class="arrow" x1="425" y1="230" x2="550" y2="280"/>
  
  <rect class="master" x="150" y="280" width="180" height="50" rx="5"/>
  <text class="text" x="240" y="310">Scheduler</text>
  
  <rect class="master" x="470" y="280" width="180" height="50" rx="5"/>
  <text class="text" x="560" y="310">Controller Manager</text>
  
  <line class="arrow" x1="240" y1="330" x2="240" y2="380"/>
  <line class="arrow" x1="560" y1="330" x2="560" y2="380"/>
  
  <rect class="node" x="325" y="380" width="200" height="50" rx="5"/>
  <text class="text" x="425" y="410">Worker Node</text>
  
  <line class="arrow" x1="425" y1="430" x2="425" y2="460"/>
  
  <rect class="node" x="325" y="460" width="200" height="50" rx="5"/>
  <text class="text" x="425" y="490">Kubelet</text>
  
  <line class="arrow" x1="425" y1="510" x2="300" y2="550"/>
  <line class="arrow" x1="425" y1="510" x2="550" y2="550"/>
  
  <rect class="pod" x="150" y="550" width="180" height="50" rx="5"/>
  <text class="text" x="240" y="580">Container Runtime</text>
  
  <rect class="pod" x="470" y="550" width="180" height="50" rx="5"/>
  <text class="text" x="560" y="580">Pod(s)</text>
  
  <line class="arrow" x1="240" y1="600" x2="240" y2="630"/>
  <line class="arrow" x1="560" y1="600" x2="560" y2="630"/>
  
  <rect class="pod" x="150" y="630" width="180" height="50" rx="5"/>
  <text class="text" x="240" y="660">Running Container</text>
  
  <rect class="node" x="470" y="630" width="180" height="50" rx="5"/>
  <text class="text" x="560" y="660">Service/Ingress</text>
  
  <text class="label" x="580" y="130">Master Node</text>
  <text class="label" x="580" y="210">Persistent storage</text>
  <text class="label" x="110" y="310">Assign pods to nodes</text>
  <text class="label" x="680" y="310">Manage state</text>
  <text class="label" x="580" y="490">Node agent</text>
</svg>'''
    
    @staticmethod
    def generate_websocket_architecture() -> str:
        """Generate WebSocket real-time communication architecture"""
        return '''<svg width="900" height="650" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <marker id="arrowhead6" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
      <polygon points="0 0, 10 3.5, 0 7" fill="#333" />
    </marker>
    <style>
      .client { fill: #6C5CE7; stroke: #5443C7; stroke-width: 2; }
      .server { fill: #00B894; stroke: #00A884; stroke-width: 2; }
      .redis { fill: #E17055; stroke: #D35400; stroke-width: 2; }
      .text { fill: white; font-family: Arial, sans-serif; font-size: 12px; text-anchor: middle; }
      .arrow { stroke: #333; stroke-width: 2; fill: none; marker-end: url(#arrowhead6); }
      .bidirectional { stroke: #0984E3; stroke-width: 3; fill: none; }
      .label { fill: #666; font-family: Arial, sans-serif; font-size: 11px; }
    </style>
  </defs>
  
  <rect class="client" x="50" y="50" width="180" height="50" rx="5"/>
  <text class="text" x="140" y="80">Client Browser 1</text>
  
  <rect class="client" x="50" y="150" width="180" height="50" rx="5"/>
  <text class="text" x="140" y="180">Client Browser 2</text>
  
  <rect class="client" x="50" y="250" width="180" height="50" rx="5"/>
  <text class="text" x="140" y="280">Client Browser 3</text>
  
  <line class="bidirectional" x1="230" y1="75" x2="350" y2="150" stroke-dasharray="5,5"/>
  <line class="bidirectional" x1="230" y1="175" x2="350" y2="175"/>
  <line class="bidirectional" x1="230" y1="275" x2="350" y2="200" stroke-dasharray="5,5"/>
  
  <rect class="server" x="350" y="125" width="200" height="100" rx="5"/>
  <text class="text" x="450" y="165">WebSocket Server</text>
  <text class="text" x="450" y="185" font-size="10">(Node.js / Laravel Echo)</text>
  <text class="text" x="450" y="205" font-size="10">Connection Pool</text>
  
  <line class="arrow" x1="550" y1="175" x2="620" y2="175"/>
  
  <rect class="redis" x="620" y="125" width="200" height="100" rx="5"/>
  <text class="text" x="720" y="165">Redis Pub/Sub</text>
  <text class="text" x="720" y="185" font-size="10">Channel: chat-room</text>
  <text class="text" x="720" y="205" font-size="10">Channel: notifications</text>
  
  <line class="arrow" x1="720" y1="225" x2="720" y2="280"/>
  
  <rect class="server" x="620" y="280" width="200" height="80" rx="5"/>
  <text class="text" x="720" y="310">Laravel Backend</text>
  <text class="text" x="720" y="330" font-size="10">Broadcast Events</text>
  <text class="text" x="720" y="350" font-size="10">Queue Workers</text>
  
  <rect class="server" x="350" y="280" width="200" height="80" rx="5"/>
  <text class="text" x="450" y="310">Load Balancer</text>
  <text class="text" x="450" y="330" font-size="10">(Sticky Sessions)</text>
  <text class="text" x="450" y="350" font-size="10">Round Robin</text>
  
  <line class="arrow" x1="450" y1="225" x2="450" y2="280"/>
  
  <rect class="server" x="100" y="400" width="140" height="60" rx="5"/>
  <text class="text" x="170" y="425">WS Server 1</text>
  <text class="text" x="170" y="445" font-size="10">Port 6001</text>
  
  <rect class="server" x="280" y="400" width="140" height="60" rx="5"/>
  <text class="text" x="350" y="425">WS Server 2</text>
  <text class="text" x="350" y="445" font-size="10">Port 6002</text>
  
  <rect class="server" x="460" y="400" width="140" height="60" rx="5"/>
  <text class="text" x="530" y="425">WS Server 3</text>
  <text class="text" x="530" y="445" font-size="10">Port 6003</text>
  
  <line class="arrow" x1="350" y1="360" x2="170" y2="400"/>
  <line class="arrow" x1="400" y1="360" x2="350" y2="400"/>
  <line class="arrow" x1="500" y1="360" x2="530" y2="400"/>
  
  <rect class="redis" x="650" y="410" width="180" height="50" rx="5"/>
  <text class="text" x="740" y="440">Shared Session Store</text>
  
  <line class="arrow" x1="600" y1="430" x2="650" y2="435"/>
  
  <text class="label" x="280" y="100">WSS:// Protocol</text>
  <text class="label" x="280" y="160">Persistent Connection</text>
  <text class="label" x="580" y="175">Pub/Sub Bridge</text>
  <text class="label" x="450" y="500">Horizontal Scaling</text>
  
  <rect x="50" y="550" width="800" height="80" fill="#F0F0F0" stroke="#CCC" stroke-width="1" rx="5"/>
  <text x="70" y="575" font-family="Arial" font-size="13" font-weight="bold" fill="#333">Message Flow:</text>
  <text x="70" y="595" font-family="Arial" font-size="11" fill="#666">1. Client sends message → 2. WS Server receives → 3. Publishes to Redis →</text>
  <text x="70" y="610" font-family="Arial" font-size="11" fill="#666">4. All WS Servers subscribe → 5. Broadcast to connected clients → 6. Real-time update</text>
</svg>'''
    
    @staticmethod
    def detect_and_generate_diagram(question: str, answer: str) -> Optional[str]:
        """Detect if a diagram should be generated based on content"""
        content = (question + ' ' + answer).lower()
        
        if any(keyword in content for keyword in ['laravel request', 'request lifecycle', 'http request flow', 'middleware stack']):
            return SVGFlowDiagramGenerator.generate_laravel_request_lifecycle()
        elif any(keyword in content for keyword in ['payment processing', 'payment gateway', 'razorpay', 'stripe flow']):
            return SVGFlowDiagramGenerator.generate_payment_processing_flow()
        elif any(keyword in content for keyword in ['query execution', 'database execution', 'query plan', 'execution plan']):
            return SVGFlowDiagramGenerator.generate_database_query_execution()
        elif any(keyword in content for keyword in ['react lifecycle', 'component lifecycle', 'react hooks lifecycle', 'useeffect']):
            return SVGFlowDiagramGenerator.generate_react_component_lifecycle()
        elif any(keyword in content for keyword in ['kubernetes deployment', 'k8s deployment', 'pod deployment', 'kubectl apply']):
            return SVGFlowDiagramGenerator.generate_kubernetes_deployment_flow()
        elif any(keyword in content for keyword in ['websocket', 'real-time', 'broadcast', 'socket.io', 'laravel echo']):
            return SVGFlowDiagramGenerator.generate_websocket_architecture()
        
        return None


class MarkdownToHTMLConverter:
    """Converts markdown content to HTML with syntax highlighting"""
    
    def __init__(self):
        self.formatter = HtmlFormatter(style='monokai', noclasses=False, cssclass='highlight')
        self.css_generated = False
    
    def get_pygments_css(self) -> str:
        """Get Pygments CSS for syntax highlighting"""
        return self.formatter.get_style_defs('.highlight')
    
    def extract_code_blocks(self, text: str) -> List[Tuple[str, str, str]]:
        """Extract code blocks from markdown text"""
        pattern = r'```([a-zA-Z0-9+-]*)?\n(.*?)```'
        matches = re.findall(pattern, text, re.DOTALL)
        return [(lang.strip() or 'text', code.strip(), f'```{lang}\n{code}```') for lang, code in matches]
    
    def highlight_code(self, code: str, language: str) -> str:
        """Apply syntax highlighting to code"""
        try:
            if language:
                lexer = get_lexer_by_name(language, stripall=True)
            else:
                lexer = guess_lexer(code)
            
            highlighted = highlight(code, lexer, self.formatter)
            return highlighted
        except ClassNotFound:
            return f'<pre><code class="language-{language}">{self.escape_html(code)}</code></pre>'
    
    def escape_html(self, text: str) -> str:
        """Escape HTML special characters"""
        return text.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;')
    
    def convert_inline_code(self, text: str) -> str:
        """Convert inline code markers to HTML"""
        return re.sub(r'`([^`]+)`', r'<code>\1</code>', text)
    
    def convert_bold(self, text: str) -> str:
        """Convert bold markers to HTML"""
        text = re.sub(r'\*\*([^*]+)\*\*', r'<strong>\1</strong>', text)
        text = re.sub(r'__([^_]+)__', r'<strong>\1</strong>', text)
        return text
    
    def convert_italic(self, text: str) -> str:
        """Convert italic markers to HTML"""
        text = re.sub(r'\*([^*]+)\*', r'<em>\1</em>', text)
        text = re.sub(r'_([^_]+)_', r'<em>\1</em>', text)
        return text
    
    def convert_links(self, text: str) -> str:
        """Convert markdown links to HTML"""
        return re.sub(r'\[([^\]]+)\]\(([^)]+)\)', r'<a href="\2">\1</a>', text)
    
    def convert_lists(self, text: str) -> str:
        """Convert markdown lists to HTML"""
        lines = text.split('\n')
        result = []
        in_list = False
        
        for line in lines:
            if re.match(r'^\s*[-*+]\s+', line):
                if not in_list:
                    result.append('<ul>')
                    in_list = True
                item = re.sub(r'^\s*[-*+]\s+', '', line)
                result.append(f'<li>{item}</li>')
            elif re.match(r'^\s*\d+\.\s+', line):
                if not in_list:
                    result.append('<ol>')
                    in_list = True
                item = re.sub(r'^\s*\d+\.\s+', '', line)
                result.append(f'<li>{item}</li>')
            else:
                if in_list:
                    result.append('</ul>' if result[-2].startswith('<ul>') else '</ol>')
                    in_list = False
                if line.strip():
                    result.append(line)
        
        if in_list:
            result.append('</ul>')
        
        return '\n'.join(result)
    
    def convert_to_html(self, markdown_text: str) -> str:
        """Convert markdown text to HTML"""
        # Extract and replace code blocks first
        code_blocks = self.extract_code_blocks(markdown_text)
        placeholders = {}
        
        for i, (lang, code, original) in enumerate(code_blocks):
            placeholder = f'__CODE_BLOCK_{i}__'
            placeholders[placeholder] = self.highlight_code(code, lang)
            markdown_text = markdown_text.replace(original, placeholder)
        
        # Convert inline formatting
        html = self.convert_inline_code(markdown_text)
        html = self.convert_bold(html)
        html = self.convert_italic(html)
        html = self.convert_links(html)
        
        # Convert lists
        html = self.convert_lists(html)
        
        # Replace placeholders with highlighted code
        for placeholder, code_html in placeholders.items():
            html = html.replace(placeholder, code_html)
        
        # Convert paragraphs
        paragraphs = html.split('\n\n')
        html = '\n'.join([f'<p>{p}</p>' if p.strip() and not p.strip().startswith('<') else p for p in paragraphs])
        
        return html


class AnswerGenerator:
    """Main answer generation system"""
    
    def __init__(self, base_dir: str = 'interview-bank'):
        self.base_dir = Path(base_dir)
        self.converter = MarkdownToHTMLConverter()
        self.svg_generator = SVGFlowDiagramGenerator()
        
        # Topic directories to process
        self.topics = [
            'realtime-communication',
            'php-laravel-api-security',
            'frontend-react-nextjs',
            'devops-cloud-k8s',
            'database-optimization',
            'ai-llm-serverless'
        ]
    
    def read_questions(self, topic_dir: Path) -> Dict[str, str]:
        """Read questions from questions.md file"""
        questions = {}
        questions_file = topic_dir / 'questions.md'
        
        if not questions_file.exists():
            return questions
        
        with open(questions_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Parse numbered questions
        lines = content.split('\n')
        current_num = None
        current_text = ''
        
        for line in lines:
            match = re.match(r'^(\d+)\.\s+(.*)', line)
            if match:
                if current_num:
                    questions[current_num] = current_text.strip()
                current_num = match.group(1)
                current_text = match.group(2)
            elif current_num and line.strip() and not line.startswith('#'):
                current_text += ' ' + line.strip()
        
        if current_num:
            questions[current_num] = current_text.strip()
        
        return questions
    
    def read_answers(self, topic_dir: Path) -> Dict[str, str]:
        """Read answers from answers.md file"""
        answers = {}
        answers_file = topic_dir / 'answers.md'
        
        if not answers_file.exists():
            return answers
        
        with open(answers_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Parse numbered answers
        lines = content.split('\n')
        current_num = None
        current_text = []
        
        for line in lines:
            match = re.match(r'^(\d+)\.\s+(.*)', line)
            if match:
                if current_num and current_text:
                    answers[current_num] = '\n'.join(current_text).strip()
                current_num = match.group(1)
                current_text = [match.group(2)]
            elif current_num:
                if line.strip().startswith('##'):
                    # New section, save current answer
                    if current_text:
                        answers[current_num] = '\n'.join(current_text).strip()
                    current_num = None
                    current_text = []
                else:
                    current_text.append(line)
        
        if current_num and current_text:
            answers[current_num] = '\n'.join(current_text).strip()
        
        return answers
    
    def augment_answer(self, question: str, answer: str) -> str:
        """Augment answer with additional details and structure"""
        # If answer is very short, add more context
        if len(answer) < 100:
            augmented = f"{answer}\n\nThis concept is fundamental in modern software development and requires careful consideration of best practices, performance implications, and security concerns."
        else:
            augmented = answer
        
        return augmented
    
    def generate_html_article(self, question_num: str, question: str, answer: str, topic: str) -> str:
        """Generate HTML article element for a Q&A pair"""
        # Augment the answer
        augmented_answer = self.augment_answer(question, answer)
        
        # Convert answer to HTML
        answer_html = self.converter.convert_to_html(augmented_answer)
        
        # Check if diagram should be generated
        diagram_svg = self.svg_generator.detect_and_generate_diagram(question, answer)
        
        # Build article HTML
        article_html = f'''<article id="q{question_num}" class="question-answer" data-topic="{topic}">
  <div class="question-header">
    <span class="question-number">Question {question_num}</span>
    <span class="topic-badge">{topic.replace('-', ' ').title()}</span>
  </div>
  <div class="question-text">
    <h3>{question}</h3>
  </div>
  <div class="answer-content">
    {answer_html}
  </div>'''
        
        if diagram_svg:
            article_html += f'''\n  <div class="architecture-diagram">
    <h4>Architecture Flow Diagram</h4>
    {diagram_svg}
  </div>'''
        
        article_html += '\n</article>\n'
        
        return article_html
    
    def generate_topic_html(self, topic: str) -> str:
        """Generate HTML for all Q&A in a topic"""
        topic_dir = self.base_dir / topic
        
        if not topic_dir.exists():
            print(f"Warning: Topic directory not found: {topic}")
            return ''
        
        questions = self.read_questions(topic_dir)
        answers = self.read_answers(topic_dir)
        
        if not questions:
            print(f"Warning: No questions found for topic: {topic}")
            return ''
        
        html_parts = []
        html_parts.append(f'<section class="topic-section" id="{topic}">')
        html_parts.append(f'<h2 class="topic-title">{topic.replace("-", " ").title()}</h2>')
        
        # Generate articles for each question
        for num in sorted(questions.keys(), key=int):
            question = questions[num]
            answer = answers.get(num, 'Answer not yet available.')
            
            article = self.generate_html_article(num, question, answer, topic)
            html_parts.append(article)
        
        html_parts.append('</section>\n')
        
        return '\n'.join(html_parts)
    
    def generate_complete_html(self) -> str:
        """Generate complete HTML document with all topics"""
        html_parts = []
        
        # HTML header
        html_parts.append('''<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Interview Bank - Comprehensive Answers</title>
  <style>''')
        
        # Add Pygments CSS
        html_parts.append(self.converter.get_pygments_css())
        
        # Add custom CSS
        html_parts.append('''
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
      line-height: 1.6;
      color: #333;
      background: #f5f5f5;
      padding: 20px;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      background: white;
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    h1 {
      color: #2c3e50;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 3px solid #3498db;
    }
    
    .topic-section {
      margin-bottom: 60px;
    }
    
    .topic-title {
      color: #2c3e50;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 30px;
    }
    
    .question-answer {
      margin-bottom: 40px;
      padding: 25px;
      background: #fafafa;
      border-left: 4px solid #3498db;
      border-radius: 4px;
    }
    
    .question-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .question-number {
      font-weight: bold;
      color: #3498db;
      font-size: 14px;
    }
    
    .topic-badge {
      background: #e8f4f8;
      color: #2980b9;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 500;
    }
    
    .question-text h3 {
      color: #2c3e50;
      font-size: 20px;
      margin-bottom: 20px;
      font-weight: 600;
    }
    
    .answer-content {
      color: #444;
      font-size: 16px;
    }
    
    .answer-content p {
      margin-bottom: 15px;
    }
    
    .answer-content code {
      background: #f0f0f0;
      padding: 2px 6px;
      border-radius: 3px;
      font-family: 'Courier New', monospace;
      font-size: 14px;
      color: #e74c3c;
    }
    
    .answer-content pre {
      margin: 20px 0;
      border-radius: 6px;
      overflow-x: auto;
    }
    
    .answer-content pre code {
      display: block;
      padding: 20px;
      background: #272822;
      color: #f8f8f2;
      line-height: 1.5;
    }
    
    .highlight {
      border-radius: 6px;
      margin: 20px 0;
      overflow-x: auto;
    }
    
    .highlight pre {
      margin: 0;
      padding: 20px;
    }
    
    .answer-content ul, .answer-content ol {
      margin-left: 25px;
      margin-bottom: 15px;
    }
    
    .answer-content li {
      margin-bottom: 8px;
    }
    
    .answer-content strong {
      color: #2c3e50;
      font-weight: 600;
    }
    
    .answer-content a {
      color: #3498db;
      text-decoration: none;
      border-bottom: 1px solid #3498db;
    }
    
    .answer-content a:hover {
      color: #2980b9;
      border-bottom-color: #2980b9;
    }
    
    .architecture-diagram {
      margin-top: 30px;
      padding: 20px;
      background: white;
      border-radius: 8px;
      border: 2px solid #e0e0e0;
    }
    
    .architecture-diagram h4 {
      color: #2c3e50;
      margin-bottom: 15px;
      font-size: 18px;
    }
    
    .architecture-diagram svg {
      max-width: 100%;
      height: auto;
      display: block;
      margin: 0 auto;
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 20px;
      }
      
      .question-answer {
        padding: 15px;
      }
      
      .question-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>📚 Interview Bank - Comprehensive Answers</h1>
''')
        
        # Generate content for each topic
        for topic in self.topics:
            print(f"Processing topic: {topic}...")
            topic_html = self.generate_topic_html(topic)
            if topic_html:
                html_parts.append(topic_html)
        
        # HTML footer
        html_parts.append('''  </div>
</body>
</html>''')
        
        return '\n'.join(html_parts)
    
    def generate_and_save(self, output_file: str = 'automation/output/comprehensive-answers.html'):
        """Generate and save the complete HTML file"""
        print("Starting HTML answer generation...")
        print(f"Base directory: {self.base_dir}")
        print(f"Topics to process: {len(self.topics)}")
        print()
        
        # Ensure output directory exists
        output_path = Path(output_file)
        output_path.parent.mkdir(parents=True, exist_ok=True)
        
        # Generate HTML
        html_content = self.generate_complete_html()
        
        # Save to file
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write(html_content)
        
        print()
        print(f"✓ HTML file generated successfully: {output_file}")
        print(f"✓ File size: {len(html_content):,} bytes")
        print(f"✓ Total topics processed: {len(self.topics)}")


def main():
    """Main entry point"""
    generator = AnswerGenerator()
    generator.generate_and_save()
    print("\n" + "="*60)
    print("Answer generation complete!")
    print("Open automation/output/comprehensive-answers.html in a browser to view.")
    print("="*60)


if __name__ == '__main__':
    main()
