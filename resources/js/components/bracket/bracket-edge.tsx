import { BaseEdge, type EdgeProps } from '@xyflow/react';

export interface BracketEdgeData extends Record<string, unknown> {
    laneX?: number;
    straight?: boolean;
}

export function BracketEdge({
    id,
    sourceX,
    sourceY,
    targetX,
    targetY,
    data,
    style,
    markerEnd,
}: EdgeProps) {
    const edgeData = data as BracketEdgeData | undefined;

    const path = edgeData?.straight
        ? `M ${sourceX},${sourceY} H ${targetX}`
        : (() => {
              const laneX =
                  edgeData?.laneX ??
                  sourceX + (targetX - sourceX) * 0.55;

              return `M ${sourceX},${sourceY} H ${laneX} V ${targetY} H ${targetX}`;
          })();

    return <BaseEdge id={id} path={path} style={style} markerEnd={markerEnd} />;
}
