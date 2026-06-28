import { Handle, Position } from '@xyflow/react';
import type { Node, NodeProps } from '@xyflow/react';
import type { TrophyNodeData } from './types';

type TrophyNode = Node<TrophyNodeData, 'trophy'>;

export const TROPHY_SIZE = 168;

export function TrophyNode({ data }: NodeProps<TrophyNode>) {
    return (
        <div className="relative">
            <Handle
                type="target"
                position={Position.Left}
                className="!size-1.5 !border-0 !bg-wc-gold"
            />
            <img
                src="/images/fifa-world-cup-trophy.png"
                alt={`${data.label} champion`}
                className="rounded-xl border border-wc-gold/35 object-contain shadow-lg"
                style={{ width: TROPHY_SIZE, height: TROPHY_SIZE }}
            />
        </div>
    );
}
