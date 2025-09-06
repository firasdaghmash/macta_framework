// BPMN Parser and Procedure Generator
class BpmnParser {
    constructor() {
        this.namespaces = ['bpmn:', 'bpmn2:'];
    }

    async parseBpmnXml(xmlContent) {
        try {
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xmlContent, 'text/xml');
            
            if (xmlDoc.getElementsByTagName('parsererror').length > 0) {
                return { success: false, error: 'Invalid XML format' };
            }

            const elements = new Map();
            const tasks = [];
            const gateways = [];
            const startEvents = [];
            const endEvents = [];
            const lanes = new Map();
            const participants = new Map();

            // Extract lanes and their assignments
            const laneElements = xmlDoc.querySelectorAll('lane, bpmn\\:lane, bpmn2\\:lane');
            laneElements.forEach(lane => {
                const laneId = lane.getAttribute('id');
                const laneName = lane.getAttribute('name') || laneId;
                
                // Get flow node references in this lane
                const flowNodeRefs = Array.from(lane.querySelectorAll('flowNodeRef, bpmn\\:flowNodeRef, bpmn2\\:flowNodeRef'))
                    .map(ref => ref.textContent.trim());
                
                lanes.set(laneId, {
                    id: laneId,
                    name: laneName,
                    flowNodeRefs
                });
                
                // Map each flow node to its lane
                flowNodeRefs.forEach(nodeId => {
                    participants.set(nodeId, laneName);
                });
            });

            // Extract all elements with IDs
            const allElements = xmlDoc.querySelectorAll('[id]');
            
            allElements.forEach(element => {
                const id = element.getAttribute('id');
                const name = element.getAttribute('name') || id;
                const tagName = element.tagName.toLowerCase();
                
                // Extract documentation/description
                let description = name;
                const docElement = element.querySelector('documentation, bpmn\\:documentation, bpmn2\\:documentation');
                if (docElement) {
                    description = docElement.textContent.trim() || name;
                }
                
                // Get lane/owner information
                const owner = participants.get(id) || this.determineOwnerByType(tagName);
                
                // Extract conditions for gateways
                let conditions = [];
                if (tagName.includes('gateway')) {
                    const outgoingFlows = xmlDoc.querySelectorAll(`sequenceFlow[sourceRef="${id}"], bpmn\\:sequenceFlow[sourceRef="${id}"], bpmn2\\:sequenceFlow[sourceRef="${id}"]`);
                    outgoingFlows.forEach(flow => {
                        const flowName = flow.getAttribute('name');
                        if (flowName) {
                            conditions.push(flowName);
                        }
                    });
                }
                
                const elementData = {
                    id,
                    name,
                    description,
                    owner,
                    conditions,
                    type: tagName,
                    element
                };

                elements.set(id, elementData);

                // Categorize elements
                if (tagName.includes('task')) {
                    tasks.push(elementData);
                } else if (tagName.includes('gateway')) {
                    gateways.push(elementData);
                } else if (tagName.includes('startevent')) {
                    startEvents.push(elementData);
                } else if (tagName.includes('endevent')) {
                    endEvents.push(elementData);
                }
            });

            // Generate basic documentation
            const documentation = this.generateBasicDocumentation(tasks, gateways, startEvents, endEvents);

            return {
                success: true,
                elements,
                tasks,
                gateways,
                startEvents,
                endEvents,
                documentation,
                rawXml: xmlContent
            };

        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    generateBasicDocumentation(tasks, gateways, startEvents, endEvents) {
        const processName = 'Business Process';
        const totalSteps = tasks.length + gateways.length;
        const decisionPoints = gateways.length;
        
        // Estimate duration (10-15 min per task, 1 min per gateway)
        const taskTime = tasks.length * 12; // average 12 minutes per task
        const gatewayTime = gateways.length * 1; // 1 minute per gateway
        const totalMinutes = taskTime + gatewayTime;
        
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        const durationText = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;

        return {
            processOverview: {
                name: processName,
                description: 'No description available',
                totalSteps,
                decisionPoints,
                estimatedDuration: durationText,
                complexityLevel: totalSteps > 10 ? 'Complex' : totalSteps > 5 ? 'Medium' : 'Simple'
            },
            processStatistics: {
                totalSteps,
                decisionPoints,
                estimatedDuration: durationText,
                complexityLevel: totalSteps > 10 ? 'Very Complex' : totalSteps > 5 ? 'Complex' : 'Simple'
            }
        };
    }

    determineOwnerByType(tagName) {
        if (tagName.includes('usertask')) {
            return 'User/Operator';
        } else if (tagName.includes('servicetask') || tagName.includes('scripttask')) {
            return 'System';
        } else if (tagName.includes('manualtask')) {
            return 'Manual Operator';
        } else if (tagName.includes('businessruletask')) {
            return 'Business Rules Engine';
        } else if (tagName.includes('gateway')) {
            return 'System/Process Owner';
        } else if (tagName.includes('startevent')) {
            return 'Process Initiator';
        } else if (tagName.includes('endevent')) {
            return 'System';
        } else {
            return 'Process Owner';
        }
    }
}

class ProcedureGenerator {
    constructor(bpmnData) {
        this.bpmnData = bpmnData;
    }

    generateProcedureDocumentation() {
        try {
            if (!this.bpmnData || !this.bpmnData.success) {
                return { success: false, error: 'Invalid BPMN data' };
            }

            const procedureSteps = this.generateProcedureSteps();
            const decisionPoints = this.generateDecisionPoints();
            
            const documentation = {
                processOverview: this.bpmnData.documentation.processOverview,
                processStatistics: this.bpmnData.documentation.processStatistics,
                procedureSteps,
                decisionPoints
            };

            return {
                success: true,
                documentation
            };

        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    generateProcedureSteps() {
        const steps = [];
        let stepNumber = 1;

        // Add start events
        if (this.bpmnData.startEvents && this.bpmnData.startEvents.length > 0) {
            this.bpmnData.startEvents.forEach(event => {
                steps.push({
                    number: stepNumber++,
                    title: event.name || 'Process Start',
                    type: 'start',
                    description: event.description || event.name || 'Process Start',
                    details: event.description || 'This marks the beginning of the process.',
                    responsible: event.owner || 'Process Initiator',
                    estimatedTime: '< 1 minute'
                });
            });
        }

        // Add tasks
        if (this.bpmnData.tasks && this.bpmnData.tasks.length > 0) {
            this.bpmnData.tasks.forEach(task => {
                const taskType = this.getTaskType(task.type);
                steps.push({
                    number: stepNumber++,
                    title: task.name || 'Task',
                    type: 'activity',
                    description: task.description || task.name || 'Task to be completed',
                    details: task.description || 'Complete this task as described.',
                    responsible: task.owner || this.getResponsibleByTaskType(task.type),
                    estimatedTime: this.getEstimatedTime(task.type)
                });
            });
        }

        // Add gateways
        if (this.bpmnData.gateways && this.bpmnData.gateways.length > 0) {
            this.bpmnData.gateways.forEach(gateway => {
                steps.push({
                    number: stepNumber++,
                    title: gateway.name || 'Decision Point',
                    type: 'decision',
                    description: gateway.description || 'Decision gateway that routes the process flow based on conditions.',
                    details: gateway.description || 'Evaluate conditions and route to appropriate path.',
                    responsible: gateway.owner || 'System/Process Owner',
                    estimatedTime: '< 1 minute',
                    conditions: gateway.conditions || []
                });
            });
        }

        // Add end events
        if (this.bpmnData.endEvents && this.bpmnData.endEvents.length > 0) {
            this.bpmnData.endEvents.forEach(event => {
                steps.push({
                    number: stepNumber++,
                    title: event.name || 'Process End',
                    type: 'end',
                    description: event.description || event.name || 'Process End',
                    details: event.description || 'This marks the completion of the process.',
                    responsible: event.owner || 'System',
                    estimatedTime: '< 1 minute'
                });
            });
        }

        return steps;
    }

    getTaskType(type) {
        if (type.includes('usertask')) return 'User Task';
        if (type.includes('servicetask')) return 'Service Task';
        if (type.includes('scripttask')) return 'Script Task';
        if (type.includes('manualtask')) return 'Manual Task';
        if (type.includes('businessruletask')) return 'Business Rule Task';
        return 'Task';
    }

    getResponsibleByTaskType(type) {
        if (type.includes('usertask')) return 'User/Operator';
        if (type.includes('servicetask')) return 'System';
        if (type.includes('scripttask')) return 'System';
        if (type.includes('manualtask')) return 'Manual Operator';
        if (type.includes('businessruletask')) return 'Business Rules Engine';
        return 'Process Owner';
    }

    getEstimatedTime(type) {
        if (type.includes('usertask')) return '5-15 minutes';
        if (type.includes('servicetask')) return '< 2 minutes';
        if (type.includes('scripttask')) return '< 1 minute';
        if (type.includes('manualtask')) return '10-30 minutes';
        if (type.includes('businessruletask')) return '< 1 minute';
        return '5 minutes';
    }

    generateDecisionPoints() {
        const decisionPoints = [];
        
        if (this.bpmnData.gateways && this.bpmnData.gateways.length > 0) {
            this.bpmnData.gateways.forEach((gateway, index) => {
                // Extract actual outgoing paths and conditions from the gateway
                const outgoingPaths = this.extractGatewayPaths(gateway.id);
                
                decisionPoints.push({
                    id: gateway.id,
                    name: gateway.name || `Decision Point ${index + 1}`,
                    type: this.getGatewayType(gateway.type),
                    description: gateway.description || this.getGatewayDescription(gateway.name, outgoingPaths),
                    conditions: outgoingPaths.conditions,
                    actions: outgoingPaths.actions,
                    paths: outgoingPaths.paths
                });
            });
        }

        return decisionPoints;
    }

    extractGatewayPaths(gatewayId) {
        const paths = [];
        const conditions = [];
        const actions = [];
        
        try {
            if (this.bpmnData.rawXml) {
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(this.bpmnData.rawXml, 'text/xml');
                
                // Find all sequence flows from this gateway
                const sequenceFlows = xmlDoc.querySelectorAll('sequenceFlow');
                const outgoingFlows = Array.from(sequenceFlows).filter(flow => 
                    flow.getAttribute('sourceRef') === gatewayId
                );
                
                outgoingFlows.forEach(flow => {
                    const flowName = flow.getAttribute('name') || 'Unnamed Path';
                    const targetRef = flow.getAttribute('targetRef');
                    const conditionExpression = flow.querySelector('conditionExpression');
                    
                    // Find target element name
                    const targetElement = xmlDoc.querySelector(`[id="${targetRef}"]`);
                    const targetName = targetElement ? targetElement.getAttribute('name') || targetRef : targetRef;
                    
                    paths.push({
                        name: flowName,
                        target: targetName,
                        condition: conditionExpression ? conditionExpression.textContent : null
                    });
                    
                    conditions.push(flowName);
                    actions.push(`Proceed to: ${targetName}`);
                });
                
                // If no paths found, provide defaults
                if (paths.length === 0) {
                    conditions.push('Default Condition');
                    actions.push('Continue Process');
                    paths.push({ name: 'Default', target: 'Next Step', condition: null });
                }
            }
        } catch (error) {
            console.warn('Error extracting gateway paths:', error);
            conditions.push('Default Condition');
            actions.push('Continue Process');
            paths.push({ name: 'Default', target: 'Next Step', condition: null });
        }
        
        return { conditions, actions, paths };
    }

    getGatewayType(type) {
        if (type && type.includes('exclusive')) return 'Exclusive Gateway (XOR)';
        if (type && type.includes('inclusive')) return 'Inclusive Gateway (OR)';
        if (type && type.includes('parallel')) return 'Parallel Gateway (AND)';
        return 'Exclusive Gateway';
    }

    getGatewayDescription(name, paths) {
        if (name && paths.conditions.length > 0) {
            return `Decision point "${name}" with ${paths.conditions.length} possible path(s): ${paths.conditions.join(', ')}`;
        }
        return 'Routes process flow based on conditions';
    }
}

// Create global instance
const bpmnParser = new BpmnParser();

