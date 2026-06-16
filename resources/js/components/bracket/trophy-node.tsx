import { Handle, Position } from '@xyflow/react';
import type { Node, NodeProps } from '@xyflow/react';
import type { TrophyNodeData } from './types';

type TrophyNode = Node<TrophyNodeData, 'trophy'>;

export function TrophyNode({ data }: NodeProps<TrophyNode>) {
    return (
        <div className="flex w-[170px] flex-col items-center gap-2 rounded-xl border border-wc-gold/40 bg-wc-ink px-4 py-5 text-center shadow-lg">
            <Handle
                type="target"
                position={Position.Left}
                className="!size-1.5 !border-0 !bg-wc-gold"
            />
            <img
                src="/images/world-cup-icon.png"
                alt=""
                className="size-14 object-contain"
            />
            <span className="font-display text-base tracking-wider text-wc-gold uppercase">
                {data.label}
            </span>
            <span className="font-mono text-[10px] tracking-wider text-wc-surface/55 uppercase">
                Champion
            </span>
        </div>
    );
}
