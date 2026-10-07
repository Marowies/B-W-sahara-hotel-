import * as T from '../vendor/three.module.js';

// Batch only static siblings. Preserve parent transforms, visibility, layouts and window pivots.
// Translucent glass panels retain their individual transparency sorting.
export function batchStaticMeshes(root) {
  let before = 0, after = 0;
  root.traverse(object => { if (object.isMesh) before++; });
  function visit(group) {
    for (const child of [...group.children]) if (child.isGroup) visit(child);
    const buckets = new Map();
    for (const mesh of group.children) {
      if (!mesh.isMesh || Array.isArray(mesh.material) || mesh.children.length) continue;
      if (mesh.material.transparent && mesh.material.opacity < .999) continue;
      if (mesh.geometry.morphAttributes.position || mesh.isSkinnedMesh) continue;
      const attrs = Object.keys(mesh.geometry.attributes).sort();
      const signature = attrs.map(name => `${name}:${mesh.geometry.attributes[name].itemSize}:${mesh.geometry.attributes[name].normalized}`).join('|');
      const key = [mesh.material.uuid, mesh.castShadow, mesh.receiveShadow, mesh.renderOrder, signature].join('/');
      if (!buckets.has(key)) buckets.set(key, []);
      buckets.get(key).push(mesh);
    }
    for (const meshes of buckets.values()) {
      if (meshes.length < 2) continue;
      const geometries = meshes.map(mesh => {
        mesh.updateMatrix();
        const geometry = mesh.geometry.index ? mesh.geometry.toNonIndexed() : mesh.geometry.clone();
        geometry.applyMatrix4(mesh.matrix);
        return geometry;
      });
      const merged = new T.BufferGeometry();
      for (const name of Object.keys(geometries[0].attributes)) {
        const attributes = geometries.map(geometry => geometry.attributes[name]);
        const total = attributes.reduce((sum, attr) => sum + attr.array.length, 0);
        const array = new attributes[0].array.constructor(total);
        let offset = 0;
        for (const attribute of attributes) { array.set(attribute.array, offset); offset += attribute.array.length; }
        merged.setAttribute(name, new T.BufferAttribute(array, attributes[0].itemSize, attributes[0].normalized));
      }
      merged.computeBoundingSphere();
      const batched = new T.Mesh(merged, meshes[0].material);
      batched.castShadow = meshes[0].castShadow;
      batched.receiveShadow = meshes[0].receiveShadow;
      batched.renderOrder = meshes[0].renderOrder;
      batched.name = 'static-batch';
      for (const mesh of meshes) { group.remove(mesh); mesh.geometry.dispose(); }
      geometries.forEach(geometry => geometry.dispose());
      group.add(batched);
    }
  }
  visit(root);
  root.traverse(object => { if (object.isMesh) after++; });
  root.userData.batching = { before, after };
}
